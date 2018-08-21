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

use Eurolink\Log\ProcessorInterface;

/**
 * Memory Processor
 */
class AbstractProcessor extends ProcessorInterface
{
    /**
     * Processor threshold level.
     *
     * @var integer
     */
    protected $level = Logger::DEBUG;

    /**
     * Setup memory processor
     *
     * @param integer $level  The logging level threshold at which this handler will be triggered.
     */
    public function __construct($level = Logger::DEBUG)
    {
        $this->setLevel($level);
    }

    /**
     * Sets minimum logging level at which this handler will be triggered.
     *
     * @param  integer|string $level Level or level name
     *
     * @return self
     */
    public function setLevel($level)
    {
        $this->level = Logger::toLevelCode($level);

        return $this;
    }

    /**
     * Gets minimum logging level at which this handler will be triggered.
     *
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Determine whether processor should deal with this record.
     *
     * @param integer $level The event level of record to handle
     *
     * @return boolean
     */
    public function threshold($level)
    {
        return $level <= $this->getLevel();
    }
}