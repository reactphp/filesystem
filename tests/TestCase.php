<?php

namespace React\Tests\Filesystem;

use React\EventLoop\LoopInterface;

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function mockAdapter(LoopInterface $loop = null)
    {
        if ($loop === null) {
            $loop = $this->getMock('React\EventLoop\StreamSelectLoop');
        }

        return $this->getMock('React\Filesystem\AdapterInterface', [
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
    }
}
