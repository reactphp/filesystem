<?php

namespace React\Tests\Filesystem\Stream;

use React\Filesystem\Stream\WritableStream;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;
use React\Tests\Filesystem\TestCase;

class ReadableStreamTest extends TestCase
{
    public function classNamesProvider()
    {
        return [
            [
                'React\Filesystem\Stream\ReadableStream',
                true,
            ],
            [
                'React\Filesystem\Stream\DuplexStream',
                false,
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
            ])))
        ;

        $mock = $this->getMock($className, [
            'readChunk',
        ], [
            $path,
            $fileDescriptor,
            $filesystem,
        ]);

        if ($className == 'React\Filesystem\Stream\DuplexStream') {
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
            ])))
        ;

        $mock = $this->getMock($className, [
            'readChunk',
        ], [
            $path,
            $fileDescriptor,
            $filesystem,
        ]);

        if ($className == 'React\Filesystem\Stream\DuplexStream') {
            $mock->resume();
        }
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testClose($className, $stat)
    {
        $path = 'foo.bar';
        $fd = '0123456789abcdef';

        $filesystem = $this->mockAdapter();

        if ($stat) {
            $filesystem
                ->expects($this->once())
                ->method('stat')
                ->with($path)
                ->will($this->returnValue(new RejectedPromise()))
            ;
        }

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
            ->with($fd)
            ->will($this->returnValue($promise))
        ;

        $stream = $this->getMock($className, [
            'emit',
            'removeAllListeners',
        ], [
            $path,
            $fd,
            $filesystem,
        ]);

        $stream
            ->expects($this->at(0))
            ->method('emit')
            ->with('close', [$stream])
        ;

        $stream
            ->expects($this->at(1))
            ->method('removeAllListeners')
            ->with()
        ;

        $stream->close();
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testAlreadyClosed($className, $stat)
    {
        $path = 'foo.bar';
        $fd = '0123456789abcdef';
        $filesystem = $this->mockAdapter();

        if ($stat) {
            $filesystem
                ->expects($this->once())
                ->method('stat')
                ->with($path)
                ->will($this->returnValue(new RejectedPromise()))
            ;
        }

        $filesystem
            ->expects($this->once())
            ->method('close')
            ->with($fd)
            ->will($this->returnValue(new RejectedPromise()))
        ;


        $stream = (new $className($path, $fd, $filesystem));
        $stream->close();
        $stream->close();
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testPipe($className, $stat)
    {
        $path = 'foo.bar';
        $fileDescriptor = '0123456789abcdef';

        $filesystem = $this->mockAdapter();

        if ($stat) {
            $filesystem
                ->expects($this->once())
                ->method('stat')
                ->with($path)
                ->will($this->returnValue(new RejectedPromise()))
            ;
        }

        $stream = new $className($path, $fileDescriptor, $filesystem);
        $destination = new WritableStream($path, $fileDescriptor, $filesystem);

        $this->assertSame($destination, $stream->pipe($destination));
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testReadChunk($className, $stat)
    {
        $path = 'foo.bar';
        $fileDescriptor = '0123456789abcdef';

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

        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->at(0))
            ->method('stat')
            ->with($path)
            ->will($this->returnValue(\React\Promise\resolve([
                'size' => 16384,
            ])))
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

        $filesystem
            ->expects($this->at(3))
            ->method('close')
            ->with($fileDescriptor)
            ->will($this->returnValue(new FulfilledPromise()))
        ;

        $mock = $this->getMock($className, [
            'isReadable',
            'emit',
        ], [
            $path,
            $fileDescriptor,
            $filesystem,
        ]);

        if ($className == 'React\Filesystem\Stream\DuplexStream') {
            $mock->resume();
        }
    }
}
