<?php

namespace React\Tests\Filesystem\Stream;

use React\Filesystem\Eio\ReadableStream;
use React\Filesystem\Eio\WritableStream;

class ReadableStreamTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $path = 'foo.bar';
        $fileDescriptor = '0123456789abcdef';

        $promise = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $promise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) {
                $resolveCb([
                    'size' => 123,
                ]);
            }))
        ;

        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $this->getMock('React\Filesystem\Eio\ReadableStream', [
            'readChunk',
        ], [
            $path,
            $fileDescriptor,
            $filesystem,
        ]);
    }

    public function testResume()
    {
        $path = 'foo.bar';
        $fileDescriptor = '0123456789abcdef';

        $promise = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $promise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) {
                $resolveCb([
                    'size' => 123,
                ]);
            }))
        ;

        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $this->getMock('React\Filesystem\Eio\ReadableStream', [
            'readChunk',
        ], [
            $path,
            $fileDescriptor,
            $filesystem,
        ]);
    }

    public function testClose()
    {
        $path = 'foo.bar';
        $fileDescriptor = '0123456789abcdef';

        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'close',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);


        $promise = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $promise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) {
                $resolveCb();
            }))
        ;

        $filesystem
            ->expects($this->once())
            ->method('close')
            ->with($fileDescriptor)
            ->will($this->returnValue($promise))
        ;

        $stream = $this->getMock('React\Filesystem\Eio\ReadableStream', [
            'emit',
        ], [
            $path,
            $fileDescriptor,
            $filesystem,
        ]);

        $stream
            ->expects($this->once())
            ->method('emit')
            ->with('close', [$stream])
        ;

        $this->assertTrue($stream->isReadable());
        $stream->close();
        $this->assertTrue(!$stream->isReadable());
    }

    public function testPipe()
    {
        $path = 'foo.bar';
        $fileDescriptor = '0123456789abcdef';

        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'read',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $stream = new ReadableStream($path, $fileDescriptor, $filesystem);
        $destination = new WritableStream($path, $fileDescriptor, $filesystem);

        $this->assertSame($destination, $stream->pipe($destination));
    }

    public function testReadChunk()
    {
        $path = 'foo.bar';
        $fileDescriptor = '0123456789abcdef';

        $statPromise = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $statPromise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) {
                $resolveCb([
                    'size' => 16384,
                ]);
            }))
        ;

        $readPromise = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $readPromise
            ->expects($this->exactly(2))
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) {
                $resolveCb('foo.bar' . (string)microtime(true));
            }))
        ;

        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'stat',
            'read',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $filesystem
            ->expects($this->at(0))
            ->method('stat')
            ->with($path)
            ->will($this->returnValue($statPromise))
        ;

        $filesystem
            ->expects($this->at(1))
            ->method('read')
            ->with($fileDescriptor, 8192, 0)
            ->will($this->returnValue($readPromise))
        ;

        $filesystem
            ->expects($this->at(2))
            ->method('read')
            ->with($fileDescriptor, 8192, 8192)
            ->will($this->returnValue($readPromise))
        ;

        $this->getMock('React\Filesystem\Eio\ReadableStream', [
            'isReadable',
            'emit',
        ], [
            $path,
            $fileDescriptor,
            $filesystem,
        ]);
    }
}
