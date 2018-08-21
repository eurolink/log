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

use ReflectionClass;

/**
 * Test Logger
 *
 * Check for correct operation of the standard features.
 */
class LoggerTest extends \PHPUnit_Framework_TestCase
{
    private $logger;
    private $handler;

    public function setUp()
    {
        $this->handler = new Handler\Stream('php://memory', Logger::DEBUG, []);

        $this->logger = new Logger(__CLASS__, [
            'handlers' => [$this->handler]
        ]);
    }

    public function tearDown()
    {
        unset($this->handler);
        unset($this->logger);
    }

    private function assertLastLineEquals(HandlerInterface $handler)
    {
        $this->assertEquals($handler->getLastLine(), $handler->getLastLineFromStream());
    }

    public function testLogLevels()
    {
        $r = new ReflectionClass($this->logger);
        $levels = $r->getConstants();
        $levels = array_flip($levels);
        ksort($levels);

        $this->assertEquals(8, count($levels));

        return $levels;
    }

    /**
     * @depends testLogLevels
     */
    public function testLogLineWithLevelCode(array $levels)
    {
        foreach ($levels as $code => $name) {
            $result = $this->logger->log($code, __METHOD__);
            $this->assertEquals(null, $result);
            $this->assertLastLineEquals($this->handler);
        }
    }
}