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
 * Describes a processor instance
 */
interface ProcessorInterface
{
    /**
     * Process the event record.
     *
     * @param array $event Log event record.
     */
    public function __invoke(array &$event);
}
