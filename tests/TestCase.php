<?php

namespace React\Tests\Filesystem;

use React\EventLoop\LoopInterface;

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $tmpDir;

    protected function mockAdapter(LoopInterface $loop = null)
    {
        if ($loop === null) {
            $loop = $this->getMock('React\EventLoop\StreamSelectLoop');
        }

        $mock = $this->getMock('React\Filesystem\AdapterInterface', [
            '__construct',
            'getLoop',
            'setFilesystem',
            'setInvoker',
            'callFilesystem',
            'mkdir',
            'rmdir',
            'unlink',
            'chmod',
            'chown',
            'stat',
            'ls',
            'touch',
            'open',
            'read',
            'write',
            'close',
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
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('react-filesystem-tests-') . DIRECTORY_SEPARATOR;
        mkdir($this->tmpDir, 0777, true);
    }

    public function tearDown()
    {
        rmdir($this->tmpDir);
    }
}
