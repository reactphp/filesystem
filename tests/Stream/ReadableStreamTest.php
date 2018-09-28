<?php

namespace React\Tests\Filesystem\Stream;

use Exception;
use React\Filesystem\Stream\DuplexStream;
use React\Filesystem\Stream\ReadableStream;
use React\Filesystem\Stream\WritableStream;
use React\Tests\Filesystem\TestCase;

class ReadableStreamTest extends TestCase
{
    public function classNamesProvider()
    {
        return [
            [
                ReadableStream::class,
            ],
            [
                DuplexStream::class,
            ],
        ];
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testConstruct($className)
    {
        $path = 'foo.bar';
        $fileDescriptor = '0123456789abcdef';

        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue(\React\Promise\resolve([
                'size' => 123,
            ])));

        $mock = $this->getMockBuilder($className)
            ->setMethods([
                'readChunk',
            ])
            ->setConstructorArgs([
                $path,
                $fileDescriptor,
                $filesystem,
            ])
            ->getMock();

        if ($className === DuplexStream::class) {
            $mock->resume();
        }
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testResume($className)
    {
        $path = 'foo.bar';
        $fileDescriptor = '0123456789abcdef';

        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue(\React\Promise\resolve([
                'size' => 123,
            ])));

        $mock = $this->getMockBuilder($className)
            ->setMethods([
                'readChunk',
            ])
            ->setConstructorArgs([
                $path,
                $fileDescriptor,
                $filesystem,
            ])
            ->getMock();

        if ($className === DuplexStream::class) {
            $mock->pause();
            $mock->resume();
        }
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testClose($className)
    {
        $path = 'foo.bar';
        $fd = '0123456789abcdef';

        $filesystem = $this->mockAdapter();

        $filesystem
            ->method('stat')
            ->with($path)
            ->will($this->returnValue(\React\Promise\reject(new Exception('test'))));

        $filesystem
            ->expects($this->once())
            ->method('close')
            ->with($fd)
            ->will($this->returnValue(\React\Promise\resolve()));

        $stream = $this->getMockBuilder($className)
            ->setMethods([
                'emit',
                'removeAllListeners',
            ])
            ->setConstructorArgs([
                $path,
                $fd,
                $filesystem,
            ])
            ->getMock();

        $stream
            ->expects($this->at(0))
            ->method('emit')
            ->with('close', [$stream]);

        $stream
            ->expects($this->at(1))
            ->method('removeAllListeners')
            ->with();

        $stream->close();
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testAlreadyClosed($className)
    {
        $path = 'foo.bar';
        $fd = '0123456789abcdef';
        $filesystem = $this->mockAdapter();

        $filesystem
            ->method('stat')
            ->with($path)
            ->will($this->returnValue(\React\Promise\reject(new Exception('test'))));

        $filesystem
            ->expects($this->once())
            ->method('close')
            ->with($fd)
            ->will($this->returnValue(\React\Promise\reject(new Exception('test'))));

        $stream = (new $className($path, $fd, $filesystem));
        $stream->close();
        $stream->close();
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testPipe($className)
    {
        $path = 'foo.bar';
        $fileDescriptor = '0123456789abcdef';

        $filesystem = $this->mockAdapter();

        $filesystem
            ->method('stat')
            ->with($path)
            ->will($this->returnValue(\React\Promise\reject(new Exception('test'))));

        $stream = new $className($path, $fileDescriptor, $filesystem);
        $destination = new WritableStream($path, $fileDescriptor, $filesystem);

        $this->assertSame($destination, $stream->pipe($destination));
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testReadChunk($className)
    {
        $offset = 0;
        $path = 'foo.bar';
        $fileDescriptor = '0123456789abcdef';

        $readPromise = \React\Promise\resolve()->then(function () {
            return 'foo.bar' . microtime(true);
        });
        $readPromise2 = clone $readPromise;

        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->at($offset++))
            ->method('stat')
            ->with($path)
            ->will($this->returnValue(\React\Promise\resolve([
                'size' => 16384,
            ])));

        $filesystem
            ->expects($this->at($offset++))
            ->method('read')
            ->with($fileDescriptor, 8192, 0)
            ->will($this->returnValue($readPromise));

        $filesystem
            ->expects($this->at($offset++))
            ->method('read')
            ->with($fileDescriptor, 8192, 8192)
            ->will($this->returnValue($readPromise2));

        $filesystem
            ->expects($this->at($offset++))
            ->method('close')
            ->with($fileDescriptor)
            ->will($this->returnValue(\React\Promise\resolve()));

        $mock = $this->getMockBuilder($className)
            ->setMethods([
                'isReadable',
                'emit',
            ])
            ->setConstructorArgs([
                $path,
                $fileDescriptor,
                $filesystem,
            ])
            ->getMock();

        if ($className === DuplexStream::class) {
            $mock->resume();
        }
    }
}
