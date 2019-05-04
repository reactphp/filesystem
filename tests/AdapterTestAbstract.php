<?php

namespace React\Tests\Filesystem;

use DateTime;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Filesystem;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Node\LinkInterface;
use React\Filesystem\Node\NodeInterface;
use React\Filesystem\ObjectStream;
use React\Filesystem\ObjectStreamSink;

abstract class AdapterTestAbstract extends TestCase
{
    /** @var LoopInterface */
    protected $loop;

    /** @var AdapterInterface */
    protected $adapter;

    /** @var FilesystemInterface */
    protected $filesystem;

    protected $onFinish;
    protected $onCleanup;

    /** @return AdapterInterface */
    abstract public function createAdapter();

    public function setUp() {
        parent::setUp();

        $this->loop = Factory::create();
        $this->adapter = $this->createAdapter();
        $this->filesystem = Filesystem::createFromAdapter($this->adapter);

        \clearstatcache($this->tmpDir.'testdir');
        \clearstatcache($this->tmpDir.'testdir2');

        \clearstatcache($this->tmpDir.'testfile');
        \clearstatcache($this->tmpDir.'testfile2');
    }

    public function isSupported()
    {
        $this->assertTrue($this->adapter->isSupported());
    }

    public function testGetLoop()
    {
        $this->assertSame($this->loop, $this->adapter->getLoop());
    }

    public function testGetFilesystem()
    {
        $this->assertSame($this->filesystem, $this->adapter->getFilesystem());
    }

    public function testSetFilesystem()
    {
        $fs = Filesystem::create($this->loop);
        $this->adapter->setFilesystem($fs);

        $this->assertSame($fs, $this->adapter->getFilesystem());
        $this->adapter->setFilesystem($this->filesystem);
    }

    public function testMkdirAndRmdir()
    {
        $path = $this->tmpDir.'testdir';
        @\rmdir($path);

        $this->await($this->adapter->mkdir($path), $this->adapter->getLoop());
        $this->assertNotFalse(\realpath($path));

        $this->await($this->adapter->rmdir($path), $this->adapter->getLoop());
        $this->assertFalse(\realpath($path));
    }

    public function testUnlink()
    {
        $path = $this->tmpDir . \uniqid('', true);
        $this->assertTrue(\touch($path, \time(), \time()));

        $this->await($this->adapter->unlink($path), $this->adapter->getLoop());
        $this->assertFalse(\file_exists($path));
    }

    /**
     * @group permissions
     */
    public function testChmod()
    {
        $path = $this->tmpDir . \uniqid('', true);
        $this->assertTrue(\touch($path, \time(), \time()));

        $this->await($this->adapter->chmod($path, 0660), $this->adapter->getLoop());
        $this->assertSame(0660, (\fileperms($path) & 0660));
        \unlink($path);
    }

    /**
     * @group permissions
     */
    public function testChown()
    {
        if(\DIRECTORY_SEPARATOR === '\\') {
            return $this->markTestSkipped('Unsupported on Windows');
        }

        $path = $this->tmpDir . \uniqid('', true);
        $this->assertTrue(\touch($path, \time(), \time()));

        $this->await($this->adapter->chown($path, 0, 2), $this->adapter->getLoop());
        $stat = \stat($path);

        $this->assertSame(0, $stat['uid']);
        $this->assertSame(2, $stat['gid']);
        @\unlink($path);
    }

    public function testStat()
    {
        $path = $this->tmpDir . \uniqid('', true);
        $this->assertTrue(\touch($path, \time(), \time()));

        $stat = $this->await($this->adapter->stat($path), $this->adapter->getLoop());

        $realStat = \stat($path);
        $realStat = [
            'dev'     => $realStat['dev'],
            'ino'     => $realStat['ino'],
            'mode'    => $realStat['mode'],
            'nlink'   => $realStat['nlink'],
            'uid'     => $realStat['uid'],
            'size'    => $realStat['size'],
            'gid'     => $realStat['gid'],
            'rdev'    => $realStat['rdev'],
            'blksize' => $realStat['blksize'],
            'blocks'  => $realStat['blocks'],
            'atime'   => (new DateTime('@' . $realStat['atime'])),
            'mtime'   => (new DateTime('@' . $realStat['mtime'])),
            'ctime'   => (new DateTime('@' . $realStat['ctime'])),
        ];

        $this->assertEquals($realStat, $stat);
        @\unlink($path);
    }

    public function testLs()
    {
        $path = $this->tmpDir . \uniqid('', true);
        $this->assertTrue(\touch($path, \time(), \time()));

        $ls = $this->await($this->adapter->ls($this->tmpDir), $this->adapter->getLoop());
        @\unlink($path);

        $this->assertSame(1, \count($ls));
        $this->assertInstanceOf(FileInterface::class, $ls[0]);
    }

    public function testLsStream()
    {
        $path = $this->tmpDir . \uniqid('', true);
        $this->assertTrue(\touch($path, time(), time()));

        $stream = $this->adapter->lsStream($this->tmpDir);
        $this->assertInstanceOf(ObjectStream::class, $stream);

        $ls = $this->await(ObjectStreamSink::promise($stream), $this->adapter->getLoop());
        @\unlink($path);

        $this->assertSame(1, \count($ls));
        $this->assertInstanceOf(FileInterface::class, $ls[0]);
    }

    public function testTouch()
    {
        $path = $this->tmpDir . \uniqid('', true);
        $this->await($this->adapter->touch($path), $this->adapter->getLoop());

        $this->assertTrue(file_exists($path));
        @\unlink($path);
    }

    public function testTouchExisting()
    {
        $path = $this->tmpDir . \uniqid('', true);
        \touch($path, \time() - 50, \time() - 50);
        $this->await($this->adapter->touch($path), $this->adapter->getLoop());

        $this->assertTrue(file_exists($path));
        @\unlink($path);
    }

    public function testOpenReadWriteClose()
    {
        $path = $this->tmpDir . \uniqid('', true);

        $file = $this->await($this->adapter->open($path, 'cw'), $this->adapter->getLoop());

        $length = $this->await($this->adapter->write($file, 'hello world', 11, 0), $this->adapter->getLoop());
        $this->assertSame(11, $length);

        $this->await($this->adapter->close($file), $this->adapter->getLoop());

        $file2 = $this->await($this->adapter->open($path, 'r'), $this->adapter->getLoop());

        $contents = $this->await($this->adapter->read($file2, 11, 0), $this->adapter->getLoop());
        $this->assertSame('hello world', $contents);

        $this->await($this->adapter->close($file2), $this->adapter->getLoop());
        @\unlink($path);
    }

    public function testRename()
    {
        $path = $this->tmpDir . \uniqid('', true);
        $path2 = $this->tmpDir . \uniqid('', true);
        $this->assertTrue(\touch($path, \time(), \time()));

        $this->await($this->adapter->rename($path, $path2), $this->adapter->getLoop());
        $this->assertFalse(\file_exists($path));
        $this->assertTrue(\file_exists($path2));

        @\unlink($path);
        @\unlink($path2);
    }

    public function testConstructLink()
    {
        $path = $this->tmpDir.'testdir-cl';
        $path2 = $this->tmpDir.'testdir-cl2';

        $this->assertTrue(\mkdir($path));
        $this->assertTrue(\symlink($path, $path2));

        $link = $this->await($this->filesystem->constructLink($path2), $this->adapter->getLoop());
        $this->assertInstanceOf(LinkInterface::class, $link);

        @\rmdir($path);
        @\unlink($path2);
    }

    public function testSymlinkAndReadlink()
    {
        $path = $this->tmpDir.'testdir-rl';
        $path2 = $this->tmpDir.'testdir-rl2';

        $this->assertTrue(\mkdir($path));
        $this->assertFalse(\realpath($path2));

        $this->await($this->adapter->symlink($path, $path2), $this->adapter->getLoop());
        $this->assertSame(\realpath($path), \realpath(\readlink($path2))); // realpath is for Windows

        $link = $this->await($this->adapter->readlink($path2), $this->adapter->getLoop());
        $this->assertSame($path, $link);

        @\rmdir($path);
        @\unlink($path2);
    }

    public function testDetectType()
    {
        $path = $this->tmpDir . \uniqid('', true);
        $this->assertTrue(\touch($path, \time(), \time()));

        $type = $this->await($this->adapter->detectType($path), $this->adapter->getLoop());
        $this->assertInstanceOf(FileInterface::class, $type);
        \unlink($path);
    }

    public function testDetectTypeLink()
    {
        $path = $this->tmpDir . \uniqid('', true);
        $this->await($this->adapter->symlink(__FILE__, $path), $this->adapter->getLoop());

        $type = $this->await($this->adapter->detectType($path), $this->adapter->getLoop());
        $this->assertInstanceOf(LinkInterface::class, $type);
        \unlink($path);
    }

    public function testGetContents()
    {
        $contents = $this->await($this->adapter->getContents(__FILE__), $this->loop);
        $this->assertSame(\file_get_contents(__FILE__), $contents);
    }

    public function testGetContentsMinMax()
    {
        $contents = $this->await($this->adapter->getContents(__FILE__, 5, 10), $this->loop);
        $this->assertSame(\file_get_contents(__FILE__, false, null, 5, 10), $contents);
    }

    public function testPutContents()
    {
        $tempFile = $this->tmpDir . \uniqid('', true);
        $contents = \sha1_file(__FILE__);

        $this->await($this->adapter->putContents($tempFile, $contents), $this->loop);
        $this->assertSame($contents, \file_get_contents($tempFile));
    }

    public function testPutContentsOverwrite()
    {
        $tempFile = $this->tmpDir . \uniqid('', true);
        $contents = \sha1_file(__FILE__);

        \file_put_contents($tempFile, \md5($contents));

        $this->await($this->adapter->putContents($tempFile, $contents), $this->loop);
        $this->assertSame($contents, \file_get_contents($tempFile));
    }

    public function testAppendContents()
    {
        $tempFile = $this->tmpDir . \uniqid('', true);
        $contents = \sha1_file(__FILE__);

        \file_put_contents($tempFile, $contents);
        $time = \sha1(\time());
        $contents .= $time;

        $this->await($this->adapter->appendContents($tempFile, $time), $this->loop);
        $this->assertSame($contents, \file_get_contents($tempFile));
    }
}
