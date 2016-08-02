<?php

namespace React\Tests\Filesystem\Adapters;

use React\EventLoop\LoopInterface;
use React\Filesystem\ChildProcess;
use React\Filesystem\Eio;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\Pthreads;

class DirectoryTest extends AbstractAdaptersTest
{
    /**
     * @dataProvider filesystemProvider
     */
    public function testCreateRecursive(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $dir = $this->tmpDir . 'path' . DIRECTORY_SEPARATOR . 'to' . DIRECTORY_SEPARATOR . 'reactphp' . DIRECTORY_SEPARATOR . 'filesystem';
        $this->await($filesystem->dir($dir)->createRecursive(), $loop);
        $this->assertTrue(file_exists($dir));
    }
}
