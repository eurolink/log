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

/**
 * Test File Handler Logger
 *
 * Check for correct operation of the file handler features.
 */
class FileHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $logger;
    private $directory;
    private $fileDebugHandler;
    private $fileErrorHandler;

    public function setUp()
    {
        $this->directory = __DIR__ . '/logs';

        $this->fileDebugHandler = new Handler\File($this->directory, Logger::DEBUG, []);
        $this->fileErrorHandler = new Handler\File($this->directory, Logger::ERROR, [
            'extension' => 'txt',
            'prefix' => 'error_'
        ]);

        $this->logger = new Logger(__CLASS__, [
            'handlers' => [
                $this->fileDebugHandler,
                $this->fileErrorHandler
            ]
        ]);
    }

    public function tearDown()
    {
        @unlink($this->fileDebugHandler->getPath());
        @unlink($this->fileErrorHandler->getPath());

        unset($this->fileDebugHandler);
        unset($this->fileErrorHandler);

        unset($this->directory);
        unset($this->logger);
    }

    private function assertLastLineEquals(HandlerInterface $handler)
    {
        $this->assertEquals($handler->getLastLine(), $handler->getLastLineFromFile());
    }

    private function assertLastLineNotEquals(HandlerInterface $handler)
    {
        $this->assertNotEquals($handler->getLastLine(), $handler->getLastLineFromFile());
    }

    private function assertLogFileExists(HandlerInterface $handler)
    {
        $this->assertTrue(file_exists($handler->getPath()));
    }

    private function assertLogFileExtension($extension, HandlerInterface $handler)
    {
        $this->assertStringEndsWith($extension, $handler->getPath());
    }

    private function assertLogFilePrefix($prefix, HandlerInterface $handler)
    {
        $filename = basename($handler->getPath());
        $this->assertStringStartsWith($prefix, $filename);
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

    public function testLogFilesExist()
    {
        $this->assertLogFileExists($this->fileDebugHandler);
        $this->assertLogFileExists($this->fileErrorHandler);
    }

    public function testLogFileExtensionDefault()
    {
        $this->assertLogFileExtension('.log', $this->fileDebugHandler);
    }

    public function testLogFileExtensionAlternative()
    {
        $this->assertLogFileExtension('.txt', $this->fileErrorHandler);
    }

    public function testLogFilePrefixDefault()
    {
        $this->assertLogFilePrefix('log_', $this->fileDebugHandler);
    }

    public function testLogFilePrefixCustom()
    {
        $this->assertLogFilePrefix('error_', $this->fileErrorHandler);
    }

    /**
     * @depends testLogLevels
     */
    public function testWriteLineWithLevelName(array $levels)
    {
        foreach ($levels as $code => $name) {
            $result = call_user_func(array($this->logger, strtolower($name)), __METHOD__);
            $this->assertEquals(null, $result);
        }
    }

    /**
     * @depends testLogLevels
     */
    public function testWriteLineWithLogConstant(array $levels)
    {
        foreach ($levels as $code => $name) {
            $result = $this->logger->log(constant(__NAMESPACE__ . '\Logger::' . $name), __METHOD__);
            $this->assertEquals(null, $result);
        }
    }

    /**
     * @depends testLogLevels
     */
    public function testWriteLineWithLogCode(array $levels)
    {
        foreach ($levels as $code => $name) {
            $result = $this->logger->log($code, __METHOD__);
            $this->assertEquals(null, $result);
        }
    }

    /**
     * @depends testLogLevels
     */
    public function testLogLevelThreshold(array $levels)
    {
        $handler = new Handler\File($this->directory, Logger::ERROR, [
            'prefix' => 'testLogLevelThreshold_'
        ]);

        $logger = new Logger(__METHOD__, [$handler]);

        foreach ($levels as $code => $name) {
            $logger->log($code, __METHOD__);
        }

        $this->assertLastLineEquals($handler);
        $this->assertEquals(4, $handler->getLineCount());

        @unlink($handler->getPath());
    }
}