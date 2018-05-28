<?php

namespace React\Tests\Filesystem\Adapters;

use React\EventLoop\LoopInterface;
use React\Filesystem\ChildProcess;
use React\Filesystem\Eio;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\Pthreads;

/**
 * @group adapters
 */
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
        $this->assertFileExists($dir);
        $this->assertSame('0760', substr(sprintf('%o', fileperms($dir)), -4));
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testCreateRecursive(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $dir = $this->tmpDir . 'path' . DIRECTORY_SEPARATOR . 'to' . DIRECTORY_SEPARATOR . 'reactphp' . DIRECTORY_SEPARATOR . 'filesystem';
        $this->await($filesystem->dir($dir)->createRecursive(), $loop);
        $this->assertFileExists($dir);
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testRemove(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $dir = $this->tmpDir . 'path';
        mkdir($dir);
        $this->assertFileExists($dir);
        $this->await($filesystem->dir($dir)->remove(), $loop);
        $this->assertFileNotExists($dir);
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testRemoveSubDir(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $dir = $this->tmpDir . 'path';
        $subDir = $this->tmpDir . 'path' . DIRECTORY_SEPARATOR . 'sub';
        mkdir($dir);
        mkdir($subDir);
        $this->assertFileExists($dir);
        $this->assertFileExists($subDir);
        $this->await($filesystem->dir($subDir)->remove(), $loop);
        $this->assertFileExists($dir);
        $this->assertFileNotExists($subDir);
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testRemoveRecursive(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $dir = $this->tmpDir . 'path';
        $subDir = $this->tmpDir . 'path' . DIRECTORY_SEPARATOR . 'sub';
        mkdir($dir);
        mkdir($subDir);
        $this->assertFileExists($dir);
        $this->assertFileExists($subDir);
        $this->await($filesystem->dir($dir)->removeRecursive(), $loop);
        $this->assertFileNotExists($subDir);
        $this->assertFileNotExists($dir);
    }

    /**
     * @dataProvider filesystemProvider
     * @group permissions
     */
    public function testChmod(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $dir = $this->tmpDir . 'path';
        $subDir = $this->tmpDir . 'path' . DIRECTORY_SEPARATOR . 'sub';
        mkdir($dir);
        mkdir($subDir);
        chmod($dir, 0777);
        chmod($subDir, 0777);
        $this->assertFileExists($dir);
        $this->assertFileExists($subDir);
        $this->assertSame('0777', substr(sprintf('%o', fileperms($dir)), -4));
        $this->assertSame('0777', substr(sprintf('%o', fileperms($subDir)), -4));
        clearstatcache();
        $this->await($filesystem->dir($dir)->chmod(0555), $loop);
        clearstatcache();
        $this->assertSame('0555', substr(sprintf('%o', fileperms($dir)), -4));
        $this->assertSame('0777', substr(sprintf('%o', fileperms($subDir)), -4));
        clearstatcache();
    }

    /**
     * @dataProvider filesystemProvider
     * @group permissions
     */
    public function testChmodRecursive(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $dir = $this->tmpDir . 'path';
        $subDir = $this->tmpDir . 'path' . DIRECTORY_SEPARATOR . 'sub';
        mkdir($dir);
        mkdir($subDir);
        chmod($dir, 0777);
        chmod($subDir, 0777);
        $this->assertFileExists($dir);
        $this->assertFileExists($subDir);
        $this->assertSame('0777', substr(sprintf('%o', fileperms($dir)), -4));
        $this->assertSame('0777', substr(sprintf('%o', fileperms($subDir)), -4));
        clearstatcache();
        $this->await($filesystem->dir($dir)->chmodRecursive(0555), $loop);
        clearstatcache();
        $this->assertSame('0555', substr(sprintf('%o', fileperms($dir)), -4));
        $this->assertSame('0555', substr(sprintf('%o', fileperms($subDir)), -4));
        clearstatcache();
    }

    /**
     * @dataProvider filesystemProvider
     * @group permissions
     */
    public function testChown(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $dir = $this->tmpDir . 'path';
        $subDir = $this->tmpDir . 'path' . DIRECTORY_SEPARATOR . 'sub';
        mkdir($dir, 0777);
        mkdir($subDir, 0777);
        clearstatcache();
        $this->assertFileExists($dir);
        $this->assertFileExists($subDir);
        clearstatcache();
        $stat = stat($dir);
        sleep(2);
        $this->await($filesystem->dir($dir)->chown($stat['uid'], getmyuid()), $loop, 5);
        clearstatcache();
        $this->assertNotSame($stat, stat($dir));
        clearstatcache();
    }

    /**
     * @dataProvider filesystemProvider
     * @group permissions
     */
    public function testChownRecursive(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $dir = $this->tmpDir . 'path';
        $subDir = $this->tmpDir . 'path' . DIRECTORY_SEPARATOR . 'sub';
        mkdir($dir, 0777);
        mkdir($subDir, 0777);
        $this->assertFileExists($dir);
        $this->assertFileExists($subDir);
        clearstatcache();
        $stat = stat($dir);
        $subStat = stat($subDir);
        sleep(2);
        $this->await($filesystem->dir($dir)->chownRecursive(-1, getmyuid()), $loop);
        clearstatcache();
        $this->assertNotSame($stat, stat($dir));
        $this->assertNotSame($subStat, stat($subDir));
        clearstatcache();
    }
}
