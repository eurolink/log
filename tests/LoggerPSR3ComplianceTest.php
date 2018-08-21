<?php
/*
 * This file is part of the logger package.
 *
 * (c) Eurolink <info@eurolink.co>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Unit6\Log;

use ReflectionClass;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Testing PSR-3 Compliance
 *
 * Check for compatibility with PSR-3 LoggerInterface and AbstractLogger
 */
class LoggerPSR3ComplianceTest extends \PHPUnit_Framework_TestCase
{
    private $logger;
    private $handler;

    public function setUp()
    {
        $directory = __DIR__ . '/logs';

        $this->handler = new Handler\File($directory, Logger::DEBUG, []);
        $this->logger = new Logger(__CLASS__, [$this->handler]);
    }

    public function tearDown()
    {
        @unlink($this->handler->getPath());

        unset($this->handler);
        unset($this->logger);
    }

    private function assertLastLogLevel($logLevel)
    {
        $pattern = sprintf('/[%s]/', $logLevel);

        $this->assertRegExp($pattern, $this->handler->getLastLine());
        $this->assertEquals($this->handler->getLastLine(), $this->handler->getLastLineFromFile());
    }

    public function testLogLevelsFromPSR3()
    {
        $r = new ReflectionClass(new LogLevel());
        $levels = $r->getConstants();

        $this->assertEquals(8, count($levels));

        return $levels;
    }

    public function testLogLevelsFromClass()
    {
        $r = new ReflectionClass($this->logger);
        $levels = $r->getConstants();
        $levels = array_flip($levels);
        ksort($levels);

        $this->assertEquals(8, count($levels));

        return $levels;
    }

    /**
     * @expectedException \Psr\Log\InvalidArgumentException
     */
    public function testThrowsOnInvalidLevel()
    {
        $this->logger->log('invalid level', 'Foo');
    }

    public function testClassImplementsLoggerInterface()
    {
        $this->assertInstanceOf('Psr\Log\LoggerInterface', $this->logger);
        $this->assertContains('Psr\Log\LoggerInterface', array_keys(class_implements($this->logger)));
    }

    public function testClassExtendsAbstractLogger()
    {
        $this->assertInstanceOf('Psr\Log\AbstractLogger', $this->logger);
        $this->assertContains('Psr\Log\AbstractLogger', array_keys(class_parents($this->logger)));
    }

    /**
     * @depends testLogLevelsFromPSR3
     * @depends testLogLevelsFromClass
     */
    public function testClassLogLevels(array $fromPSR3, array $fromClass)
    {
        $this->assertEquals(array_keys($fromPSR3), array_values($fromClass));
    }

    /**
     * @depends testLogLevelsFromPSR3
     */
    public function testMatchLogLevels(array $levels)
    {
        foreach ($levels as $name) {
            $this->logger->log(constant('Psr\Log\LogLevel::' . strtoupper($name)), __METHOD__);
            $this->assertLastLogLevel(constant('Psr\Log\LogLevel::' . strtoupper($name)));
        }
    }
}