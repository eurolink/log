<?php
/*
 * This file is part of the logger package.
 *
 * (c) Eurolink <info@eurolink.co>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eurolink\Log\Handler;

use RuntimeException;

use Eurolink\Log\Logger;
use Eurolink\Log\AbstractHandler;
use Psr\Log\LoggerInterface;

/**
 * File Handler
 */
class File extends AbstractHandler
{
    /**
     * This holds the file handle for this instance's log file
     *
     * @var resource
     */
    private $pointer;

    /**
     * Octal notation for default permissions of the log file
     *
     * @var integer
     */
    private $defaultPermissions = 0777;

    /**
     * Path to the log file
     *
     * @var string
     */
    private $path;

    /**
     * The number of lines logged in this instance's lifetime
     *
     * @var integer
     */
    private $lineCount = 0;

    /**
     * This holds the last line logged to the logger
     *  Used for unit tests
     *
     * @var string
     */
    private $lastLine = '';

    /**
     * Handler default options
     *
     * @var array
     */
    private $options = [
        'appendContext'  => true,
        'filename'       => null,
        'writeMode'      => 'a', // write only.
        'prefix'         => 'log_',
        'extension'      => 'log',
        'flushFrequency' => 1,
        'eventFormat'    => '[{date}] [{levelName}] {message}',
        'dateFormat'     => 'Y-m-d G:i:s.u',
        'bubble'         => true
    ];

    /**
     * Setup file logger.
     *
     * @param string  $path    Path to directory for log files.
     * @param integer $level   The minimum logging level at which this handler will be triggered
     * @param array   $options Additional options for logging.
     */
    public function __construct($path, $level = Logger::DEBUG, array $options = [])
    {
        $this->options = array_merge($this->options, $options);

        parent::__construct($level, $this->options['bubble']);

        $this->setPath($path);

        $this->open();
    }

    /**
     * Attempt to open the log file ready for use.
     */
    public function open()
    {
        $this->pointer = @fopen($this->getPath(), $this->options['writeMode']);

        if ( ! $this->pointer || ! is_resource($this->pointer)) {
            throw new RuntimeException('The file could not be opened. Check permissions.');
        }
    }

    /**
     * Close the $pointer
     */
    public function close()
    {
        if ($this->pointer && is_resource($this->pointer)) {
            fclose($this->pointer);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function capture(array $event)
    {
        if ( ! $this->threshold($event['level'])) {
            return false;
        }

        // DateTime object.
        $event['date'] = $event['date']->format($this->options['dateFormat']);

        // parse event parameters.
        $entry = Logger::interpolate($this->options['eventFormat'], $event);

        if ($this->options['appendContext'] && ! empty($context)) {
            $entry .= PHP_EOL . $this->indent($this->contextToString($context));
        }

        $line = $entry . PHP_EOL;

        $this->write($line);
    }

    /**
     * Get the last line logged to the log file
     *
     * @return string
     */
    public function getLastLine()
    {
        return $this->lastLine;
    }

    /**
     * Get the last count for the log file
     *
     * @return integer
     */
    public function getLineCount()
    {
        return $this->lineCount;
    }

    /**
     * Get the file path that the log is currently writing to
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set logging path directory.
     *
     * @param string $path
     */
    public function setPath($path)
    {
        // Strip trailing separator.
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        // Create directory if path doesn't exist
        if ( ! file_exists($path)) {
            mkdir($path, $this->defaultPermissions, true);
        }

        // Append trailing directory separator.
        $this->path = $path . DIRECTORY_SEPARATOR;

        // Use an existing filename.
        if ($this->options['filename']) {
            $this->path .= $this->options['filename'];

            // Fix the extension
            if (strpos($this->options['filename'], '.log') === false &&
                strpos($this->options['filename'], '.txt') === false) {
                $this->path .= '.' . $this->options['extension'];
            }
        } else {
            // ... otherwise generate a filename.
            $this->path .= $this->options['prefix'] . date('Y-m-d') . '.' . $this->options['extension'];
        }

        // Check path exists and is writable
        if (file_exists($this->path) && ! is_writable($this->path)) {
            throw new RuntimeException('The file could not be written to. Check permissions.');
        }
    }

    /**
     * Writes a line to the log without prepending a status or timestamp
     *
     * @param string $line Line to write to the log
     *
     * @return void
     */
    public function write($line)
    {
        if ( ! $this->pointer || ! is_resource($this->pointer)) {
            throw new RuntimeException('The pointer not found.');
        }

        if (fwrite($this->pointer, $line) === false) {
            throw new RuntimeException('The file could not be written to. Check permissions have been set.');
        }

        $this->lastLine = trim($line);
        $this->lineCount++;

        if ($this->options['flushFrequency'] &&
            $this->lineCount % $this->options['flushFrequency'] === 0) {
            fflush($this->pointer);
        }
    }

    /**
     * Get the last line from the actual log file.
     *
     * https://gist.github.com/lorenzos/1711e81a9162320fde20
     *
     * @return string
     */
    public function getLastLineFromFile()
    {
        $lines = 1;
        $adaptive = true;

        $filename = $this->getPath();

        // Open file for reading.
        $h = @fopen($filename, 'r');

        if ($h === false) {
            return false;
        }

        // Sets buffer size
        $buffer = ( ! $adaptive ? 4096 : ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096)));

        // Jump to last character
        fseek($h, -1, SEEK_END);

        // Read it and adjust line number if necessary
        // (Otherwise the result would be wrong if file doesn't end with a blank line)
        if (fread($h, 1) != "\n") $lines -= 1;

        // Start reading
        $output = '';
        $chunk = '';

        // While we would like more
        while (ftell($h) > 0 && $lines >= 0) {
            // Figure out how far back we should jump
            $seek = min(ftell($h), $buffer);

            // Do the jump (backwards, relative to where we are)
            fseek($h, -$seek, SEEK_CUR);

            // Read a chunk and prepend it to our output
            $output = ($chunk = fread($h, $seek)) . $output;

            // Jump back to where we started reading
            fseek($h, -mb_strlen($chunk, '8bit'), SEEK_CUR);

            // Decrease our line counter
            $lines -= substr_count($chunk, "\n");
        }

        // While we have too many lines
        // (Because of buffer size we might have read too many)
        while ($lines++ < 0) {
            // Find first newline and remove all text before that
            $output = substr($output, strpos($output, "\n") + 1);
        }

        // Close file and return
        fclose($h);

        return trim($output);
    }

    /**
     * Indents the given string with the given indent.
     *
     * @param  string $string The string to indent
     * @param  string $indent What to use as the indent.
     *
     * @return string
     */
    protected function indent($string, $indent = '    ')
    {
        return $indent . str_replace("\n", "\n" . $indent, $string);
    }
}