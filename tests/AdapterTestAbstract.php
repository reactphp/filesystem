<?php

namespace React\Tests\Filesystem;

use DateTime;
use React\Filesystem\Filesystem;
use React\Filesystem\InstantInvoker;
use React\Filesystem\AdapterInterface;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\CallInvokerInterface;
use React\Filesystem\Node\NodeInterface;
use React\Filesystem\Node\FileInterface;

abstract class AdapterTestAbstract extends TestCase
{
    /** @var AdapterInterface */
    protected $adapter;

    /** @var FilesystemInterface */
    protected $filesystem;

    protected $onFinish;
    protected $onCleanup;

    public function setUp() {
        parent::setUp();

        clearstatcache($this->tmpDir.'testdir');
        clearstatcache($this->tmpDir.'testdir2');

        clearstatcache($this->tmpDir.'testfile');
        clearstatcache($this->tmpDir.'testfile2');
    }

    public function testLink()
    {
        $this->assertInstanceOf(NodeInterface::class, $this->filesystem->link('foo', $this->filesystem->dir('bar')));
    }

    public function testGetContents()
    {
        $contents = $this->await($this->filesystem->getContents(__FILE__), $this->adapter->getLoop());
        $this->assertSame(file_get_contents(__FILE__), $contents);
    }

    public function isSupported()
    {
        $this->assertTrue($this->filesystem->isSupported());
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

    public function testGetInvoker()
    {
        $this->assertInstanceOf(CallInvokerInterface::class, $this->adapter->getInvoker());
    }

    public function testSetInvoker()
    {
        $invoker = $this->adapter->getInvoker();
        $invoker2 = new InstantInvoker($this->adapter);
        $this->adapter->setInvoker($invoker2);

        $this->assertSame($invoker2, $this->adapter->getInvoker());
        $this->adapter->setInvoker($invoker);
    }

    public function testMkdirAndRmdir()
    {
        $path = $this->tmpDir.'testdir';
        @rmdir($path);

        $this->await($this->adapter->mkdir($path), $this->adapter->getLoop());
        $this->assertNotFalse(realpath($path));

        $this->await($this->adapter->rmdir($path), $this->adapter->getLoop());
        $this->assertFalse(realpath($path));
    }

    public function testUnlink()
    {
        $path = $this->tmpDir.'testfile';
        $this->assertTrue(touch($path, time(), time()));

        $this->await($this->adapter->unlink($path), $this->adapter->getLoop());
        $this->assertFalse(file_exists($path));
    }

    /**
     * @group permissions
     */
    public function testChmod()
    {
        $path = $this->tmpDir.'testfile';
        $this->assertTrue(touch($path, time(), time()));

        $this->await($this->adapter->chmod($path, 0400), $this->adapter->getLoop());
        $this->assertSame(0400, (fileperms($path) & 0400));
        @unlink($path);
    }

    /**
     * @group permissions
     */
    public function testChown()
    {
        if(DIRECTORY_SEPARATOR === '\\') {
            return $this->markTestSkipped('Unsupported on Windows');
        }
        
        $path = $this->tmpDir.'testfile';
        $this->assertTrue(touch($path, time(), time()));

        $this->await($this->adapter->chown($path, 0, 2), $this->adapter->getLoop());
        $stat = stat($path);

        $this->assertSame(0, $stat['uid']);
        $this->assertSame(2, $stat['gid']);
        @unlink($path);
    }

    public function testStat()
    {
        $path = $this->tmpDir.'testfile';
        $this->assertTrue(touch($path, time(), time()));

        $stat = $this->await($this->adapter->stat($path), $this->adapter->getLoop());

        $realStat = stat($path);
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
        @unlink($path);
    }

    public function testLs()
    {
        $path = $this->tmpDir.'testfile';
        $this->assertTrue(touch($path, time(), time()));

        $ls = $this->await($this->adapter->ls($this->tmpDir), $this->adapter->getLoop());
        @unlink($path);

        $this->assertSame(1, count($ls));
        $this->assertInstanceOf(NodeInterface::class, $ls[0]);
        $this->assertSame($path, $ls[0]->getPath());
    }

    public function testTouch()
    {
        $path = $this->tmpDir.'testfile';
        $ls = $this->await($this->adapter->touch($path), $this->adapter->getLoop());

        $this->assertTrue(file_exists($path));
        @unlink($path);
    }

    public function testOpenReadWriteClose()
    {
        $path = $this->tmpDir.'testfile';

        $file = $this->await($this->adapter->open($path, 'cw'), $this->adapter->getLoop());

        $length = $this->await($this->adapter->write($file, 'hello world', 11, 0), $this->adapter->getLoop());
        $this->assertSame(11, $length);

        $this->await($this->adapter->close($file), $this->adapter->getLoop());

        $file2 = $this->await($this->adapter->open($path, 'r'), $this->adapter->getLoop());

        $contents = $this->await($this->adapter->read($file2, 11, 0), $this->adapter->getLoop());
        $this->assertSame('hello world', $contents);

        $this->await($this->adapter->close($file2), $this->adapter->getLoop());
        @unlink($path);
    }

    public function testRename()
    {
        $path = $this->tmpDir.'testfile';
        $path2 = $this->tmpDir.'testfile2';
        $this->assertTrue(touch($path, time(), time()));

        $this->await($this->adapter->rename($path, $path2), $this->adapter->getLoop());
        $this->assertFalse(file_exists($path));
        $this->assertTrue(file_exists($path2));

        @unlink($path);
        @unlink($path2);
    }

    public function testSymlinkAndReadlink()
    {
        $path = $this->tmpDir.'testdir';
        $path2 = $this->tmpDir.'testdir2';

        $this->assertTrue(mkdir($path));
        $this->assertFalse(realpath($path2));

        $this->await($this->adapter->symlink($path, $path2), $this->adapter->getLoop());
        $this->assertSame($path, readlink($path2));

        $link = $this->await($this->adapter->readlink($path2), $this->adapter->getLoop());
        $this->assertSame($path, $link);

        @rmdir($path);
        @unlink($path2);
    }

    public function testDetectType()
    {
        $path = $this->tmpDir.'testfile';
        $this->assertTrue(touch($path, time(), time()));

        $type = $this->await($this->adapter->detectType($path), $this->adapter->getLoop());
        $this->assertInstanceOf(FileInterface::class, $type);
    }
}
