<?php
/*
 * This file is part of the logger package.
 *
 * (c) Eurolink <info@eurolink.co>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eurolink\Log;

/**
 * Describes a handler instance
 */
interface HandlerInterface
{
    /**
     * Handles a log event.
     *
     * All records may be passed to this method, and the handler should discard
     * those that it does not want to handle.
     *
     * The return value of this function controls the event propogation process of the handler stack.
     * Unless the propogation is interrupted (by returning true), the Logger class will keep on
     * calling further handlers in the stack with a given log event.
     *
     * @param array $record The event record to handle
     *
     * @return boolean If this handler returns true, that means that the event
     *                 has been handled and propogation should stop. If it
     *                 returns false that means the record was either not processed
     *                 or that this handler allows propogation.
     */
    public function capture(array $event);

    /**
     * Closes the current handler.
     *
     * This will be called automatically when the object is destroyed
     *
     * @return void
     */
    public function close();
}
