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
    public function testLs(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $path = $this->tmpDir . 'path';
        touch($path);
        $listing = $this->await($filesystem->dir($this->tmpDir)->ls(), $loop);
        $listing->rewind();
        $this->assertSame(1, $listing->count());
        $this->assertSame($path, $listing->current()->getPath());
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testSize(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $contents = str_repeat('a', 100);
        $path = $this->tmpDir . 'path';
        file_put_contents($path, $contents);
        mkdir($this->tmpDir . 'subPath');
        file_put_contents($this->tmpDir . 'subPath/file', $contents);
        $size = $this->await($filesystem->dir($this->tmpDir)->size(), $loop);
        $this->assertSame([
            'directories' => 1,
            'files' => 1,
            'size' => 100,
        ], $size);
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testSizeRecursive(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $contents = str_repeat('a', 100);
        $path = $this->tmpDir . 'path';
        file_put_contents($path, $contents);
        mkdir($this->tmpDir . 'subPath');
        file_put_contents($this->tmpDir . 'subPath/file', $contents);
        $size = $this->await($filesystem->dir($this->tmpDir)->sizeRecursive(), $loop);
        $this->assertSame([
            'directories' => 1,
            'files' => 2,
            'size' => 200,
        ], $size);
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testCreate(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $dir = $this->tmpDir . 'path';
        $this->await($filesystem->dir($dir)->createRecursive(), $loop);
        $this->assertTrue(file_exists($dir));
    }

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
