<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console;

/**
 * Contains all events dispatched by an Application.
 *
 * @author Francesco Levorato <git@flevour.net>
 */
final class ConsoleEvents
{
    /**
     * The COMMAND event allows you to attach listeners before any command is
     * executed by the console. It also allows you to modify the command, input and output
     * before they are handled to the command.
     *
     * @Event("Symfony\Component\Console\Event\ConsoleCommandEvent")
     *
     * @var string
     */
    const COMMAND = 'console.command';

    /**
     * The TERMINATE event allows you to attach listeners after a command is
     * executed by the console.
     *
     * @Event("Symfony\Component\Console\Event\ConsoleTerminateEvent")
     *
     * @var string
     */
    const TERMINATE = 'console.terminate';

    /**
     * The EXCEPTION event occurs when an uncaught exception appears
     * while executing Command#run().
     *
     * This event allows you to deal with the exception or
     * to modify the thrown exception.
     *
     * @Event("Symfony\Component\Console\Event\ConsoleExceptionEvent")
     *
     * @var string
     *
     * @deprecated The console.exception event is deprecated since version 3.3 and will be removed in 4.0. Use the console.error event instead.
     */
    const EXCEPTION = 'console.exception';

    /**
     * The ERROR event occurs when an uncaught exception appears or
     * a throwable error.
     *
     * This event allows you to deal with the exception/error or
     * to modify the thrown exception.
     *
     * @Event("Symfony\Component\Console\Event\ConsoleErrorEvent")
     *
     * @var string
     */
    const ERROR = 'console.error';
}
