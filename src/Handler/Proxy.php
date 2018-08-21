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

use Eurolink\Log\Logger;
use Psr\Log\LoggerInterface;

/**
 * Proxies log messages to an existing PSR-3 compliant logger.
 */
class Proxy extends AbstractHandler
{
    /**
     * PSR-3 compliant logger
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Setup the PSR-3 logger.
     *
     * @param LoggerInterface $logger The underlying PSR-3 compliant logger to which messages will be proxied
     * @param integer         $level  The minimum logging level at which this handler will be triggered
     * @param boolean         $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(LoggerInterface $logger, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function capture(array $event)
    {
        if ( ! $this->threshold($event)) {
            return false;
        }

        $this->logger->log(strtolower($event['level_name']), $event['message'], $event['context']);
    }
}