<?php

namespace React\Tests\Filesystem\Stream;

use React\Filesystem\Eio\ReadableStream;
use React\Filesystem\Eio\WritableStream;
use React\Promise\RejectedPromise;

class ReadableStreamTest extends \PHPUnit_Framework_TestCase
{
    public function classNamesProvider()
    {
        return [
            [
                'React\Filesystem\Eio\ReadableStream',
            ],
            [
                'React\Filesystem\Eio\DuplexStream',
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

        $filesystem = $this->getMock('React\Filesystem\Eio\Adapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $mock = $this->getMock($className, [
            'readChunk',
        ], [
            $path,
            $fileDescriptor,
            $filesystem,
        ]);

        if ($className == 'React\Filesystem\Eio\DuplexStream') {
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

        $filesystem = $this->getMock('React\Filesystem\Eio\Adapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $mock = $this->getMock($className, [
            'readChunk',
        ], [
            $path,
            $fileDescriptor,
            $filesystem,
        ]);

        if ($className == 'React\Filesystem\Eio\DuplexStream') {
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

        $filesystem = $this->getMock('React\Filesystem\Eio\Adapter', [
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
            ->with('end', [$stream])
        ;

        $stream
            ->expects($this->at(1))
            ->method('emit')
            ->with('close', [$stream])
        ;

        $stream
            ->expects($this->at(2))
            ->method('removeAllListeners')
            ->with()
        ;

        $stream->close();
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testAlreadyClosed($className)
    {
        $path = 'foo.bar';
        $fd = '0123456789abcdef';
        $filesystem = $this->getMock('React\Filesystem\Eio\Adapter', [
            'close',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);


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
    public function testPipe($className)
    {
        $path = 'foo.bar';
        $fileDescriptor = '0123456789abcdef';

        $filesystem = $this->getMock('React\Filesystem\Eio\Adapter', [
            'read',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $stream = new $className($path, $fileDescriptor, $filesystem);
        $destination = new WritableStream($path, $fileDescriptor, $filesystem);

        $this->assertSame($destination, $stream->pipe($destination));
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testReadChunk($className)
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

        $filesystem = $this->getMock('React\Filesystem\Eio\Adapter', [
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

        $mock = $this->getMock($className, [
            'isReadable',
            'emit',
        ], [
            $path,
            $fileDescriptor,
            $filesystem,
        ]);

        if ($className == 'React\Filesystem\Eio\DuplexStream') {
            $mock->resume();
        }
    }
}
