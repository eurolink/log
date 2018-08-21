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

/**
 * File Handler
 */
class Stream extends AbstractHandler
{
    /**
     * This holds the file handle for pipeline
     *
     * @var resource
     */
    private $pointer;

    /**
     * Pipeline for logging
     *
     * @var string
     */
    private $pipe;

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
        'eventFormat'    => '[{date}] [{levelName}] {message}',
        'dateFormat'     => 'Y-m-d G:i:s.u',
        'sequenceFormat' => "\033[{codes}m{line}\033[0m\n",
        'color'          => false,
        'bubble'         => true
    ];

    /**
     * List of streams
     *
     * @var array
     */
    private $streams = [
        'php://stdout',
        'php://stderr',
        'php://memory',
    ];

    /**
     * ANSI Escape Sequences
     *
     * SGR (Select Graphic Rendition) parameters
     *
     * @var array
     */
    protected $codes = [
        'effects' => [
            'normal' => 0, // all attributes off
            'bold' => 1, // or increased intensity
            'underscore' => 4, // single
            'blink' => 5, // slow: less than 150 per minute
            'reverse' => 7, // or inverse; swap foreground and background
            'conceal' => 8
        ],
        'foreground' => [
            'black' => 30,
            'red' => 31,
            'green' => 32,
            'yellow' => 33,
            'blue' => 34,
            'magenta' => 35,
            'cyan' => 36,
            'white' => 37
        ],
        'background' => [
            'black' => 40,
            'red' => 41,
            'green' => 42,
            'yellow' => 43,
            'blue' => 44,
            'magenta' => 45,
            'cyan' => 46,
            'white' => 47
        ]
    ];

    /**
     * Display Styles
     *
     * @var array
     */
    protected $styles = [
        'default'   => ['background' => 'black', 'foreground' => 'white',   'effects' => []],
        'debug'     => ['background' => 'black', 'foreground' => 'green',   'effects' => ['bold']],
        'info'      => ['background' => 'black', 'foreground' => 'cyan',    'effects' => ['bold']],
        'notice'    => ['background' => 'cyan',  'foreground' => 'magenta', 'effects' => ['bold']],
        'warning'   => ['background' => 'red',   'foreground' => 'yellow',  'effects' => ['bold']],
        'error'     => ['background' => 'red',   'foreground' => 'white',   'effects' => ['bold']],
        'critical'  => ['background' => 'red',   'foreground' => 'yellow',  'effects' => ['bold']],
        'alert'     => ['background' => 'red',   'foreground' => 'white',   'effects' => ['blink', 'bold']],
        'emergency' => ['background' => 'red',   'foreground' => 'yellow',  'effects' => ['blink', 'bold']]
    ];

    /**
     * Setup file logger.
     *
     * @param string  $pipe      Pipeline being used on the TTY (stdout, stderr).
     * @param integer $level     The minimum logging level at which this handler will be triggered
     * @param array   $options   Additional options for logging.
     */
    public function __construct($pipe, $level = Logger::DEBUG, array $options = [])
    {
        $this->options = array_merge($this->options, $options);

        parent::__construct($level, $this->options['bubble']);

        $this->setPipe($pipe);

        $this->open();
    }

    /**
     * Attempt to open the log file ready for use.
     */
    public function open()
    {
        // Open for reading and writing; place the file pointer at
        // the beginning of the file and truncate the file to zero
        // length. If the file does not exist, attempt to create it.
        $this->pointer = @fopen($this->getPipe(), 'w+');

        if ( ! $this->pointer || ! is_resource($this->pointer)) {
            throw new RuntimeException('The pipe could not be opened. Check permissions.');
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

        if ($this->options['color']) {
            $this->formatLine($event['levelName'], $entry);
        }

        $line = $entry . PHP_EOL;

        $this->write($line);
    }

    /**
     * Get the style settings for a particular level.
     *
     * @param string $levelName Associated level
     * @param string $entry     Log line entry.
     *
     * @return array
     */
    protected function formatLine($levelName, &$entry)
    {
        $format = $this->options['sequenceFormat'];

        $style = (isset($this->styles[$levelName]) ? $this->styles[$levelName] : $this->styles['default']);

        // prevent multiple parsing of codes for same level.
        if ( ! isset($style['codes'])) {
            $codes = $this->getCodes($style);
            $style['codes'] = implode(';', $codes);
            $this->styles[$levelName]['codes'] = $style['codes'];
        }

        $entry = strtr($format, ['{codes}' => $style['codes'], '{line}' => $entry]);
    }

    /**
     * Get style codes
     *
     * @param array $style List of style aliases.
     *
     * @return array
     */
    protected function getCodes(array $style)
    {
        $codes = [];

        if (isset($style['foreground']) &&
            isset($this->codes['foreground'][$style['foreground']])) {
            $codes[] = $this->codes['foreground'][$style['foreground']];
        }

        if (isset($style['background']) &&
            isset($this->codes['background'][$style['background']])) {
            $codes[] = $this->codes['background'][$style['background']];
        }

        if (isset($style['effects']) && ! empty($style['effects'])) {
            foreach ($style['effects'] as $type) {
                if (isset($this->codes['effects'][$type])) {
                    $codes[] = $this->codes['effects'][$type];
                }
            }
        }

        return $codes;
    }

    /**
     * Get the pipe that the log is currently writing to
     *
     * @return string
     */
    public function getPipe()
    {
        return $this->pipe;
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

    protected $bytes = 0;

    /**
     * Get the last line written to stream
     *
     * @return string
     */
    public function getLastLineFromStream()
    {
        $length = strlen($this->getLastLine());

        // add newline offset (\n).
        $length += ($this->options['color'] ? 2 : 1);

        $stat = fstat($this->pointer);
        $offset = $stat['size'] - $length;
        $ending = PHP_EOL;

        fseek($this->pointer, $offset);

        return stream_get_line($this->pointer, $length, $ending);
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
     * Set the pipe being used to write log events to.
     *
     * @param string $pipe
     */
    public function setPipe($pipe)
    {
        if ( ! in_array($pipe, $this->streams)) {
            throw new UnexpectedValueException('The pipe should be a PHP stream: ' . implode(', ', $this->streams));
        }

        $this->pipe = $pipe;
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
            throw new LogicException('The resource stream was not found.');
        }

        if (fwrite($this->pointer, $line) === false) {
            throw new RuntimeException('The resource stream could not be written to.');
        }

        $this->lastLine = trim($line);
        $this->lineCount++;
    }
}