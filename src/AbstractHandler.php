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
 * Handler Abstract Class
 *
 * Create a class to handle logging.
 */
abstract class AbstractHandler implements HandlerInterface
{
    /**
     * Cotrol whether handler permits 'bubbling' up.
     *
     * @var array
     */
    protected $bubble = true;

    /**
     * Handler threshold level.
     *
     * @var integer
     */
    protected $level = Logger::DEBUG;

    /**
     * @param integer $level  The logging level threshold at which this handler will be triggered.
     * @param boolean $bubble Determines wehther or not the handler permits event propagation.
     */
    public function __construct($level = Logger::DEBUG, $bubble = true)
    {
        $this->setLevel($level);
    }

    /**
     * Clean up events.
     */
    public function __destruct()
    {
        try {
            $this->close();
        } catch (\Exception $e) {
            // do nothing
        }
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
     * Determine whether handler should deal with this record.
     *
     * @param integer $level The event level of record to handle
     *
     * @return boolean
     */
    public function threshold($level)
    {
        return $level <= $this->getLevel();
    }

    /**
     * Sets the bubbling behavior.
     *
     * @param boolean $bubble true means that this handler allows bubbling.
     *                        false means that bubbling is not permitted.
     * @return self
     */
    public function setBubble(bool $bubble)
    {
        $this->bubble = $bubble;
    }

    /**
     * Gets the bubbling behavior.
     *
     * @return boolean true means that this handler allows bubbling.
     *                 false means that bubbling is not permitted.
     */
    public function getBubble()
    {
        return $this->bubble;
    }

    /**
     * Determine whether or not to permit propagation.
     *
     * @return boolean If true halt bubbling.
     */
    public function stopPropagation()
    {
        return (false === $this->getBubble());
    }

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        // noop
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        // noop
    }
}