<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\DependencyInjection\TypedReference;
use Symfony\Component\HttpKernel\DependencyInjection\RegisterControllerArgumentLocatorsPass;

class RegisterControllerArgumentLocatorsPassTest extends TestCase
{
    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessage Class "Symfony\Component\HttpKernel\Tests\DependencyInjection\NotFound" used for service "foo" cannot be found.
     */
    public function testInvalidClass()
    {
        $container = new ContainerBuilder();
        $container->register('argument_resolver.service')->addArgument(array());

        $container->register('foo', NotFound::class)
            ->addTag('controller.service_arguments')
        ;

        $pass = new RegisterControllerArgumentLocatorsPass();
        $pass->process($container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing "action" attribute on tag "controller.service_arguments" {"argument":"bar"} for service "foo".
     */
    public function testNoAction()
    {
        $container = new ContainerBuilder();
        $container->register('argument_resolver.service')->addArgument(array());

        $container->register('foo', RegisterTestController::class)
            ->addTag('controller.service_arguments', array('argument' => 'bar'))
        ;

        $pass = new RegisterControllerArgumentLocatorsPass();
        $pass->process($container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing "argument" attribute on tag "controller.service_arguments" {"action":"fooAction"} for service "foo".
     */
    public function testNoArgument()
    {
        $container = new ContainerBuilder();
        $container->register('argument_resolver.service')->addArgument(array());

        $container->register('foo', RegisterTestController::class)
            ->addTag('controller.service_arguments', array('action' => 'fooAction'))
        ;

        $pass = new RegisterControllerArgumentLocatorsPass();
        $pass->process($container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing "id" attribute on tag "controller.service_arguments" {"action":"fooAction","argument":"bar"} for service "foo".
     */
    public function testNoService()
    {
        $container = new ContainerBuilder();
        $container->register('argument_resolver.service')->addArgument(array());

        $container->register('foo', RegisterTestController::class)
            ->addTag('controller.service_arguments', array('action' => 'fooAction', 'argument' => 'bar'))
        ;

        $pass = new RegisterControllerArgumentLocatorsPass();
        $pass->process($container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid "action" attribute on tag "controller.service_arguments" for service "foo": no public "barAction()" method found on class "Symfony\Component\HttpKernel\Tests\DependencyInjection\RegisterTestController".
     */
    public function testInvalidMethod()
    {
        $container = new ContainerBuilder();
        $container->register('argument_resolver.service')->addArgument(array());

        $container->register('foo', RegisterTestController::class)
            ->addTag('controller.service_arguments', array('action' => 'barAction', 'argument' => 'bar', 'id' => 'bar_service'))
        ;

        $pass = new RegisterControllerArgumentLocatorsPass();
        $pass->process($container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid "controller.service_arguments" tag for service "foo": method "fooAction()" has no "baz" argument on class "Symfony\Component\HttpKernel\Tests\DependencyInjection\RegisterTestController".
     */
    public function testInvalidArgument()
    {
        $container = new ContainerBuilder();
        $container->register('argument_resolver.service')->addArgument(array());

        $container->register('foo', RegisterTestController::class)
            ->addTag('controller.service_arguments', array('action' => 'fooAction', 'argument' => 'baz', 'id' => 'bar'))
        ;

        $pass = new RegisterControllerArgumentLocatorsPass();
        $pass->process($container);
    }

    public function testAllActions()
    {
        $container = new ContainerBuilder();
        $resolver = $container->register('argument_resolver.service')->addArgument(array());

        $container->register('foo', RegisterTestController::class)
            ->setAutowired(true)
            ->addTag('controller.service_arguments')
        ;

        $pass = new RegisterControllerArgumentLocatorsPass();
        $pass->process($container);

        $locator = $container->getDefinition((string) $resolver->getArgument(0))->getArgument(0);

        $this->assertEquals(array('foo:fooAction' => new ServiceClosureArgument(new Reference('service_locator.d964744f7278cba85dee823607f8c07f'))), $locator);

        $locator = $container->getDefinition((string) $locator['foo:fooAction']->getValues()[0]);

        $this->assertSame(ServiceLocator::class, $locator->getClass());
        $this->assertFalse($locator->isPublic());
        $this->assertTrue($locator->isAutowired());

        $expected = array('bar' => new ServiceClosureArgument(new TypedReference('stdClass', 'stdClass', ContainerInterface::IGNORE_ON_INVALID_REFERENCE, false)));
        $this->assertEquals($expected, $locator->getArgument(0));
    }

    public function testExplicitArgument()
    {
        $container = new ContainerBuilder();
        $resolver = $container->register('argument_resolver.service')->addArgument(array());

        $container->register('foo', RegisterTestController::class)
            ->addTag('controller.service_arguments', array('action' => 'fooAction', 'argument' => 'bar', 'id' => 'bar'))
            ->addTag('controller.service_arguments', array('action' => 'fooAction', 'argument' => 'bar', 'id' => 'baz')) // should be ignored, the first wins
        ;

        $pass = new RegisterControllerArgumentLocatorsPass();
        $pass->process($container);

        $locator = $container->getDefinition((string) $resolver->getArgument(0))->getArgument(0);
        $locator = $container->getDefinition((string) $locator['foo:fooAction']->getValues()[0]);
        $this->assertFalse($locator->isAutowired());

        $expected = array('bar' => new ServiceClosureArgument(new TypedReference('bar', 'stdClass', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, false)));
        $this->assertEquals($expected, $locator->getArgument(0));
    }

    public function testOptionalArgument()
    {
        $container = new ContainerBuilder();
        $resolver = $container->register('argument_resolver.service')->addArgument(array());

        $container->register('foo', RegisterTestController::class)
            ->addTag('controller.service_arguments', array('action' => 'fooAction', 'argument' => 'bar', 'id' => '?bar'))
        ;

        $pass = new RegisterControllerArgumentLocatorsPass();
        $pass->process($container);

        $locator = $container->getDefinition((string) $resolver->getArgument(0))->getArgument(0);
        $locator = $container->getDefinition((string) $locator['foo:fooAction']->getValues()[0]);

        $expected = array('bar' => new ServiceClosureArgument(new TypedReference('bar', 'stdClass', ContainerInterface::IGNORE_ON_INVALID_REFERENCE, false)));
        $this->assertEquals($expected, $locator->getArgument(0));
    }
}

class RegisterTestController
{
    public function __construct(\stdClass $bar)
    {
    }

    public function fooAction(\stdClass $bar)
    {
    }

    protected function barAction(\stdClass $bar)
    {
    }
}
