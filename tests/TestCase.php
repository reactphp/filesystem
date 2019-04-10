<?php

namespace React\Tests\Filesystem;

use Clue\React\Block;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    const TIMEOUT = 30;

    protected $tmpDir;

    protected $startTime;

    protected function mockAdapter(LoopInterface $loop = null)
    {
        if ($loop === null) {
            $loop = $this->getMock('React\EventLoop\LoopInterface');
        }

        $mock = $this->getMock('React\Filesystem\AdapterInterface', [
            '__construct',
            'getLoop',
            'getFilesystem',
            'setFilesystem',
            'getInvoker',
            'setInvoker',
            'callFilesystem',
            'isSupported',
            'mkdir',
            'rmdir',
            'unlink',
            'chmod',
            'chown',
            'stat',
            'ls',
            'lsStream',
            'touch',
            'open',
            'read',
            'write',
            'close',
            'getContents',
            'putContents',
            'appendContents',
            'rename',
            'readlink',
            'symlink',
            'detectType',
        ], [
            $loop,
        ]);

        $mock
            ->expects($this->any())
            ->method('getLoop')
            ->with()
            ->will($this->returnValue($loop))
        ;

        return $mock;
    }

    public function setUp()
    {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'react-filesystem-tests' . DIRECTORY_SEPARATOR . uniqid('', true) . DIRECTORY_SEPARATOR;
        mkdir($this->tmpDir, 0777, true);
        $this->startTime = time();
    }

    protected function checkIfTimedOut($maxExecutionTime = self::TIMEOUT)
    {
        if (($this->startTime + $maxExecutionTime) <= time()) {
            $this->fail('Manual timeout');
        }
    }

    protected function setLoopTimeout(LoopInterface $loop, $maxExecutionTime = self::TIMEOUT)
    {
        $loop->addTimer($maxExecutionTime, function () use ($loop) {
            $loop->stop();
            $this->fail('Event loop timeout');
        });
    }

    public function tearDown()
    {
        $this->rmdir($this->tmpDir);
    }

    protected function rmdir($dir)
    {
        $directory = dir($dir);
        while (false !== ($entry = $directory->read())) {
            if (in_array($entry, ['.', '..'])) {
                continue;
            }

            if (is_dir($dir . $entry)) {
                $this->rmdir($dir . $entry . DIRECTORY_SEPARATOR);
                continue;
            }

            if (is_file($dir . $entry)) {
                unlink($dir . $entry);
                continue;
            }
        }
        $directory->close();
    }

    protected function await(PromiseInterface $promise, LoopInterface $loop, $timeout = self::TIMEOUT)
    {
        $result = Block\await($promise, $loop, $timeout);
        $loop->run(); // Ensure we let the loop run it's course to clean up
        return $result;
    }
}
