<?php

namespace React\Tests\Filesystem\Node;

use React\Filesystem\Node\File;
use React\Promise\Deferred;

class FileTest extends \PHPUnit_Framework_TestCase
{

    public function testGetPath()
    {
        $path = 'foo.bar';
        $this->assertSame($path, (new File($path, $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ])))->getPath());
    }

    public function testRemove()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'unlink',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $promise = $this->getMock('React\Promise\PromiseInterface');

        $filesystem
            ->expects($this->once())
            ->method('unlink')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $this->assertSame($promise, (new File($path, $filesystem))->remove());
    }

    public function testRename()
    {
        $pathFrom = 'foo.bar';
        $pathTo = 'bar.foo';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'rename',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $promise = $this->getMock('React\Promise\PromiseInterface');

        $filesystem
            ->expects($this->once())
            ->method('rename')
            ->with($pathFrom, $pathTo)
            ->will($this->returnValue($promise))
        ;

        $this->assertSame($promise, (new File($pathFrom, $filesystem))->rename($pathTo));
    }

    public function testExists()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'stat',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $promise = $this->getMock('React\Promise\PromiseInterface');

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $this->assertInstanceOf('React\Promise\PromiseInterface', (new File($path, $filesystem))->exists());
    }

    public function testSize()
    {
        $size = 1337;
        $path = __FILE__;
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'stat',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $deferred = new Deferred();
        $promise = $deferred->promise();

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $sizePromise = (new File($path, $filesystem))->size();
        $this->assertInstanceOf('React\Promise\PromiseInterface', $sizePromise);

        $callbackFired = false;
        $sizePromise->then(function ($resultSize) use ($size, &$callbackFired) {
            $this->assertSame($size, $resultSize);
            $callbackFired = true;
        });
        $deferred->resolve([
            'size' => $size,
        ]);
        $this->assertTrue($callbackFired);
    }

    public function testTime()
    {
        $times = [
            'atime' => 1,
            'ctime' => 2,
            'mtime' => 3,
        ];
        $path = __FILE__;
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'stat',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $deferred = new Deferred();
        $promise = $deferred->promise();

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $timePromise = (new File($path, $filesystem))->time();
        $this->assertInstanceOf('React\Promise\PromiseInterface', $timePromise);

        $callbackFired = false;
        $timePromise->then(function ($time) use ($times, &$callbackFired) {
            $this->assertSame($times, $time);
            $callbackFired = true;
        });
        $deferred->resolve($times);
        $this->assertTrue($callbackFired);
    }
}
