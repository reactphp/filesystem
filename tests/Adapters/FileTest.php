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
class FileTest extends AbstractAdaptersTest
{
    /**
     * @dataProvider filesystemProvider
     */
    public function testStat(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $actualStat = lstat(__FILE__);
        $result = $this->await($filesystem->file(__FILE__)->stat(), $loop);
        foreach ($actualStat as $key => $value) {
            if (!is_string($key) || in_array($key, ['atime', 'mtime', 'ctime'])) {
                continue;
            }

            $this->assertSame($actualStat[$key], $result[$key]);
        }

        $this->assertInstanceOf('DateTime', $result['atime']);
        $this->assertEquals($actualStat['atime'], $result['atime']->format('U'));
        $this->assertInstanceOf('DateTime', $result['mtime']);
        $this->assertEquals($actualStat['mtime'], $result['mtime']->format('U'));
        $this->assertInstanceOf('DateTime', $result['atime']);
        $this->assertEquals($actualStat['ctime'], $result['ctime']->format('U'));
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testTime(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $actualStat = lstat(__FILE__);
        $result = $this->await($filesystem->file(__FILE__)->time(), $loop);
        $this->assertCount(3, $result);
        $this->assertInstanceOf('DateTime', $result['atime']);
        $this->assertEquals($actualStat['atime'], $result['atime']->format('U'));
        $this->assertInstanceOf('DateTime', $result['mtime']);
        $this->assertEquals($actualStat['mtime'], $result['mtime']->format('U'));
        $this->assertInstanceOf('DateTime', $result['atime']);
        $this->assertEquals($actualStat['ctime'], $result['ctime']->format('U'));
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testSize(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $actualStat = lstat(__FILE__);
        $result = $this->await($filesystem->file(__FILE__)->size(), $loop);
        $this->assertEquals($actualStat['size'], $result);
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testExists(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $result = true;
        try {
            $this->await($filesystem->file(__FILE__)->exists(), $loop);
        } catch (\Exception $e) {
            $result = false;
        }
        $this->assertTrue($result);
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testDoesntExist(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $this->setLoopTimeout($loop);
        $rejectionReason = null;

        try {
            $this->await($filesystem->file(__FILE__ . '.' . time())->stat(), $loop);
        } catch (\Exception $e) {
            $rejectionReason = $e->getMessage();
        }

        $this->assertTrue(
            $rejectionReason === 'Path doesn\'t exist' ||
            $rejectionReason === 'No such file or directory'
        );
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testRemove(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $tempFile = $this->tmpDir . uniqid('', true);
        touch($tempFile);
        do {
            usleep(500);
            $this->checkIfTimedOut();
        } while (!file_exists($tempFile));
        $this->await($filesystem->file($tempFile)->remove(), $loop);
        $this->assertFileNotExists($tempFile);
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testCreate(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $tempFile = $this->tmpDir . uniqid('', true);
        $this->assertFileNotExists($tempFile);
        $this->await($filesystem->file($tempFile)->create(), $loop);
        $this->assertFileExists($tempFile);
        $this->assertSame('0760', substr(sprintf('%o', fileperms($tempFile)), -4));
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testTouch(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $tempFile = $this->tmpDir . uniqid('', true);
        $this->assertFileNotExists($tempFile);
        $this->await($filesystem->file($tempFile)->touch(), $loop);
        $this->assertFileExists($tempFile);
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testGetContents(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $tempFile = $this->tmpDir . uniqid('', true);
        $contents = str_pad('a', 1024*512);
        file_put_contents($tempFile, $contents);
        do {
            usleep(500);
            $this->checkIfTimedOut();
        } while (!file_exists($tempFile));
        $this->assertFileExists($tempFile);
        $fileContents = $this->await($filesystem->file($tempFile)->getContents(), $loop);
        $this->assertSame($contents, $fileContents);
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testGetBinaryContents(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'reactphp-logo.png';
        $this->assertFileExists($file);
        $fileContents = $this->await($filesystem->file($file)->getContents(), $loop);
        $this->assertSame(file_get_contents($file), $fileContents);
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testCopy(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $tempFileSource = $this->tmpDir . uniqid('source', true);
        $tempFileDestination = $this->tmpDir . uniqid('destination', true);
        $contents = str_pad('a', 33, 'b');
        file_put_contents($tempFileSource, $contents);
        do {
            usleep(500);
            $this->checkIfTimedOut();
        } while (!file_exists($tempFileSource));
        $this->assertFileExists($tempFileSource);
        $this->assertSame($contents, file_get_contents($tempFileSource));
        $this->await($filesystem->file($tempFileSource)->copy($filesystem->file($tempFileDestination)), $loop);
        $this->assertFileEquals($tempFileSource, $tempFileDestination);
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testCopyToDirectory(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $filename = uniqid('source', true);
        $tempFileSource = $this->tmpDir . $filename;
        $tempFileDestination = $this->tmpDir . uniqid('destination', true) . DIRECTORY_SEPARATOR;
        $contents = str_pad('a', 33, 'b');
        file_put_contents($tempFileSource, $contents);
        mkdir($tempFileDestination);
        do {
            usleep(500);
            $this->checkIfTimedOut();
        } while (!file_exists($tempFileSource) && !file_exists($tempFileDestination));
        $this->assertFileExists($tempFileSource);
        $this->assertSame($contents, file_get_contents($tempFileSource));
        $this->await($filesystem->file($tempFileSource)->copy($filesystem->dir($tempFileDestination)), $loop);
        do {
            usleep(500);
            $this->checkIfTimedOut();
        } while (!file_exists($tempFileDestination . $filename) || stat($tempFileDestination . $filename)['size'] == 0);
        $this->assertFileEquals($tempFileSource, $tempFileDestination . $filename);
    }

    /**
     * @dataProvider filesystemProvider
     * @group permissions
     */
    public function testChmod(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $filename = uniqid('', true);
        $tempFile = $this->tmpDir . $filename;
        touch($tempFile);
        do {
            usleep(500);
            $this->checkIfTimedOut();
        } while (!file_exists($tempFile));
        chmod($tempFile, 0777);
        $this->await($filesystem->file($tempFile)->chmod(0666), $loop);
        $this->assertSame('0666', substr(sprintf('%o', fileperms($tempFile)), -4));
    }

    /**
     * @dataProvider filesystemProvider
     * @group permissions
     */
    public function testChownUid(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $filename = uniqid('', true);
        $tempFile = $this->tmpDir . $filename;
        touch($tempFile);
        do {
            usleep(500);
            $this->checkIfTimedOut();
        } while (!file_exists($tempFile));
        $this->await($filesystem->file($tempFile)->chown(1000), $loop);
        $this->assertSame(1000, fileowner($tempFile));
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testRename(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $filenameFrom = uniqid('', true);
        $tempFileFrom = $this->tmpDir . $filenameFrom;
        file_put_contents($tempFileFrom, $filenameFrom);
        $filenameTo = uniqid('', true);
        $tempFileTo = $this->tmpDir . $filenameTo;
        $this->await($filesystem->file($tempFileFrom)->rename($tempFileTo), $loop);
        $this->assertFileExists($tempFileTo);
        $this->assertSame($filenameFrom, file_get_contents($tempFileTo));
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testPutContents(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $contents = str_repeat('abc', 1024 * 1024 * 5);
        $filename = uniqid('', true);
        $tempFile = $this->tmpDir . $filename;
        $this->await($filesystem->file($tempFile)->putContents($contents), $loop);
        $this->assertFileExists($tempFile);
        $this->assertSame($contents, file_get_contents($tempFile));
    }
    /**
     * @dataProvider filesystemProvider
     */
    public function testPutBinaryContents(LoopInterface $loop, FilesystemInterface $filesystem)
    {
        $contents = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'reactphp-logo.png');
        $filename = uniqid('', true);
        $tempFile = $this->tmpDir . $filename;
        $this->await($filesystem->file($tempFile)->putContents($contents), $loop);
        $this->assertFileExists($tempFile);
        $this->assertSame($contents, file_get_contents($tempFile));
    }
}
