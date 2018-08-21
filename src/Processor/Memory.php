<?php
/*
 * This file is part of the logger package.
 *
 * (c) Eurolink <info@eurolink.co>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eurolink\Log\Processor;

use Eurolink\Log\Logger;
use Eurolink\Log\ProcessorInterface;

/**
 * Memory Processor
 */
class Memory extends AbstractProcessor
{
    /**
     * Get system size of memory
     *
     * If true, get the real size of memory allocated from system.
     * Else, only the memory used by emalloc() is reported.
     *
     * @var bool
     */
    protected $realUsage;

    /**
     * Human readable format.
     *
     * If true, then format memory size to human readable string (MB, KB, B depending on size)
     *
     * @var bool
     */
    protected $useFormatting;

    /**
     * Setup memory processor
     *
     * @param bool $realUsage     Set this to true to get the real size of memory allocated from system.
     * @param bool $useFormatting If true, then format memory size to human readable string (MB, KB, B depending on size)
     */
    public function __construct($level = Logger::DEBUG, $realUsage = true, $useFormatting = true)
    {
        parent::__construct($level);

        $this->realUsage = (boolean) $realUsage;
        $this->useFormatting = (boolean) $useFormatting;
    }

    /**
     * Append allocated memory usage for PHP.
     *
     * @param array $record
     */
    public function __invoke(array &$event)
    {
        if ( ! $this->threshold($event['level'])) {
            return;
        }

        $event['meta']['memory_peak_usage'] = $this->get_peak_usage();
        $event['meta']['memory_usage'] = $this->get_usage;
        $event['meta']['memory_limit'] = $this->get_limit();
    }

    /**
     * Get the amount of memory allocated to PHP
     *
     * @return string
     */
    protected function get_usage()
    {
        $bytes = memory_get_usage($this->realUsage);

        return $this->formatBytes($bytes);
    }

    /**
     * Get the peak of memory allocated by PHP
     *
     * @return string
     */
    protected function get_peak_usage()
    {
        $bytes = memory_get_peak_usage($this->realUsage);

        return $this->formatBytes($bytes);
    }

    /**
     * Get memory limit defined in the php.ini
     *
     * @return string
     */
    protected function get_limit()
    {
        $bytes = ini_get('memory_limit');

        return $this->formatBytes($bytes);
    }

    /**
     * Formats bytes into a human readable string if $this->useFormatting
     * is true, otherwise return $bytes as is
     *
     * @param  int        $bytes
     * @return string|int Formatted string if $this->useFormatting is true, otherwise return $bytes as is
     */
    protected function formatBytes($bytes)
    {
        $size = (integer) $bytes;

        if ( ! $this->useFormatting) {
            return $size;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $units[$i];
    }
}