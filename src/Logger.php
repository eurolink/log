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

use DateTime;
use DateTimeZone;
use RuntimeException;
use LogicException;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

/**
 * Logger Class
 *
 * Create a logging instance.
 */
class Logger extends AbstractLogger
{
    /**
     * Detailed debug information
     */
    const DEBUG = 7;

    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    const INFO = 6;

    /**
     * Uncommon events
     */
    const NOTICE = 5;

    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    const WARNING = 4;

    /**
     * Runtime errors
     */
    const ERROR = 3;

    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    const CRITICAL = 2;

    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    const ALERT = 1;

    /**
     * Urgent action required as system is unusable
     */
    const EMERGENCY = 0;

    /**
     * Logger default options
     *
     * @var array
     */
    protected $options = [
        'microseconds' => true,
        'timezone'     => 'UTC',
        'handlers'     => [],
        'processors'   => []
    ];

    /**
     * Logger channel name
     *
     * @var string
     */
    protected $channel;

    /**
     * Set the timezone for log files
     *
     * @var DateTimeZone
     */
    private $timezone;

    /**
     * The handler stack
     *
     * @var HandlerInterface[]
     */
    protected $handlers;

    /**
     * Processors that will process all log events
     *
     * To process events of a single handler instead, add the processor on that specific handler
     *
     * @var callable[]
     */
    protected $processors;

    /**
     * Log Levels
     *
     * The numeric codes follow severity levels from the syslog protocol (RFC 5424).
     *
     * If events level is greater or equal to the threshold level,
     * the event is appended to the log.
     *
     * @var array
     */
    protected static $levels = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7
    ];

    /**
     * Logger constructor
     *
     * @param string             $channel    The logging channel name
     * @param HandlerInterface[] $handlers   Optional stack of handlers, the first one in the array is called first, etc.
     * @param DateTimeZone       $timezone   Optional timezone, if not provided date_default_timezone_get() will be used
     */
    public function __construct($channel, array $options = [])
    {
        $this->channel = $channel;

        $this->options = array_merge($this->options, $options);

        $this->setHandlers($this->options['handlers']);
        $this->setProcessors($this->options['processors']);
        $this->setTimezone($this->options['timezone']);
    }

    /**
     * Logger channel name.
     *
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Pushes a handler on to the stack.
     *
     * @param HandlerInterface $handler
     *
     * @return $this
     */
    public function pushHandler(HandlerInterface $handler)
    {
        array_unshift($this->handlers, $handler);

        return $this;
    }

    /**
     * Pops a handler from the stack
     *
     * @return HandlerInterface
     */
    public function popHandler()
    {
        if ( ! $this->handlers) {
            throw new LogicException('You tried to pop from an empty handler stack.');
        }

        return array_shift($this->handlers);
    }

    /**
     * Set handlers, replacing all existing ones.
     *
     * If a map is passed, keys will be ignored.
     *
     * @param  HandlerInterface[] $handlers
     *
     * @return $this
     */
    public function setHandlers(array $handlers = [])
    {
        $this->handlers = [];

        foreach (array_reverse($handlers) as $handler) {
            $this->pushHandler($handler);
        }

        return $this;
    }

    /**
     * Get list of handlers
     *
     * @return HandlerInterface[]
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * Set the timezone to be used for the timestamp of log records.
     *
     * @param string $tz Timezone string
     */
    public function setTimezone($tz = NULL)
    {
        if ( ! $tz ) {
            $tz = $this->options['timezone'] ?: date_default_timezone_get() ?: 'UTC';
        }

        $this->timezone = new DateTimeZone($tz);
    }

    /**
     * Adds a processor on to the stack.
     *
     * @param  callable $callback
     *
     * @return $this
     */
    public function pushProcessor(callable $callback)
    {
        array_unshift($this->processors, $callback);

        return $this;
    }

    /**
     * Removes the processor on top of the stack and returns it.
     *
     * @return callable
     */
    public function popProcessor()
    {
        if ( ! $this->processors) {
            throw new LogicException('You tried to pop from an empty processor stack.');
        }

        return array_shift($this->processors);
    }

    /**
     * Set processors, replacing all existing ones.
     *
     * If a map is passed, keys will be ignored.
     *
     * @param callable[] $processors
     *
     * @return $this
     */
    public function setProcessors(array $processors = [])
    {
        $this->processors = $processors;

        return $this;
    }

    /**
     * Get list of processors
     *
     * @return callable[]
     */
    public function getProcessors()
    {
        return $this->processors;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function log($level, $message, array $context = [])
    {
        // attempt to guess which is missing.
        $level = static::toLevelCode($level);

        // check if any handler will capture this message so we can return early and save cycles
        $handlerKey = null;

        // rewind the internal pointer of an array to its first element.
        reset($this->handlers);

        // first pass of handlers.
        while ($handler = current($this->handlers)) {
            if ($handler->threshold($level)) {
                $handlerKey = key($this->handlers);
                break;
            }

            // advance the internal array pointer of an array
            // for event propagation bubbling.
            next($this->handlers);
        }

        if (null === $handlerKey) {
            return false;
        }

        $event = [
            'level'      => $level,
            'levelName'  => static::toLevelName($level),
            'message'    => $message,
            'context'    => $context,
            'channel'    => $this->getChannel(),
            'date'       => $this->getDateTime(),
            'meta'       => []
        ];

        foreach ($this->processors as $processor) {
            // execute the __invoke in the processor.
            call_user_func($processor, $event);
        }

        // second pass of handlers
        while ($handler = current($this->handlers)) {
            // handle bubbling ...
            $handler->capture($event);

            if ($handler->stopPropagation()) {
                break;
            }

            next($this->handlers);
        }
    }

    /**
     * Interpolates context values into the message placeholders.
     * Taken from PSR-3's example implementation.
     *
     * @param  string $format  The message to log
     * @param  array  $context The context
     *
     * @return string
     */
    public static function interpolate($format, array $context = []) {
        // Build a replacement array with braces around the context keys.
        $replace = [];

        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = (is_scalar($val) ? $val : json_encode($val));
        }

        // Interpolate replacement values into the message and return
        return strtr($format, $replace);
    }

    /**
     * Gets the correctly formatted Date/Time for the log entry.
     *
     * PHP date() will always generate 000000 since it takes an integer
     * parameter, whereas DateTime::format() does support microseconds.
     *
     * http://stackoverflow.com/a/17909891/
     *
     * @return string
     */
    private function getDateTime()
    {
        if ($this->options['microseconds']) {
            $dt = DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)), $this->timezone);
        } else {
            $dt = new DateTime('', $this->timezone);
        }

        $dt->setTimezone($this->timezone);

        return $dt;
    }

    /**
     * Parse the numeric log level to its name.
     *
     * @param integer $level The Log Level of the message
     *
     * @return string
     */
    public static function toLevelName($level)
    {
        $names = array_keys(static::$levels);

        if ( ! isset($names[$level])) {
            throw new InvalidArgumentException('Invalid log level name: ' . $level);
        }

        return $names[$level];
    }


    /**
     * Converts PSR-3 levels to Monolog ones if necessary
     *
     * @param string|integer Level number or name (PSR-3).
     *
     * @return integer
     */
    public static function toLevelCode($level)
    {
        if (is_string($level)) {
            $constant = __CLASS__ . '::' . strtoupper($level);
            if (defined($constant)) {
                return constant($constant);
            }

            throw new InvalidArgumentException('Level "' . $level . '" is not defined, use one of: ' . implode(', ', array_keys(static::$levels)));
        }

        return $level;
    }
}
