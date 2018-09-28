<?php

namespace React\Tests\Filesystem\Stream;

use React\Filesystem\Stream\DuplexStream;
use React\Filesystem\Stream\WritableStream;
use React\Tests\Filesystem\TestCase;

class WritableStreamTest extends TestCase
{
    public function classNamesProvider()
    {
        return [
            [
                WritableStream::class,
            ],
            [
                DuplexStream::class,
            ],
        ];
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testWrite($className)
    {
        $offset = 0;
        $path = 'foo.bar';
        $fd = '0123456789abcdef';
        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->at($offset++))
            ->method('write')
            ->with($fd, 'abc', 3, 0)
            ->will($this->returnValue($fd));

        $filesystem
            ->expects($this->at($offset++))
            ->method('write')
            ->with($fd, 'def', 3, 3)
            ->will($this->returnValue($fd));

        $filesystem
            ->expects($this->at($offset++))
            ->method('write')
            ->with($fd, 'ghijklmnopqrstuvwxyz', 20, 6)
            ->will($this->returnValue($fd));

        $stream = (new $className($path, $fd, $filesystem));
        $stream->write('abc');
        $stream->write('def');
        $stream->write('ghijklmnopqrstuvwxyz');
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testIsWritable($className)
    {
        $path = 'foo.bar';
        $fd = '0123456789abcdef';
        $filesystem = $this->mockAdapter();

        $this->assertTrue((new $className($path, $fd, $filesystem))->isWritable());
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testIsNotWritable($className)
    {
        $path = 'foo.bar';
        $fd = '0123456789abcdef';
        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->once())
            ->method('close')
            ->with($fd)
            ->will($this->returnValue(\React\Promise\reject()));

        $stream = (new $className($path, $fd, $filesystem));
        $stream->close();
        $this->assertFalse($stream->isWritable());
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testEnd($className)
    {
        $data = 'iahbfeq';
        $stream = $this->getMockBuilder($className)
            ->setMethods([
                'write',
                'close',
            ])
            ->setConstructorArgs([
                'foo.bar',
                '0123456789abcdef',
                $this->mockAdapter(),
            ])
            ->getMock();

        $stream
            ->expects($this->once())
            ->method('write')
            ->with($data);

        $stream
            ->expects($this->once())
            ->method('close')
            ->with();

        $stream->end($data);
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function testEndNoWrite($className)
    {
        
        $stream = $this->getMockBuilder($className)
            ->setMethods([
                'write',
                'close',
            ])
            ->setConstructorArgs([
                'foo.bar',
                '0123456789abcdef',
                $this->mockAdapter(),
            ])
            ->getMock();

        $stream
            ->expects($this->never())
            ->method('write')
            ->with();

        $stream
            ->expects($this->once())
            ->method('close')
            ->with();

        $stream->end();
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
            ->expects($this->once())
            ->method('close')
            ->with($fd)
            ->will($this->returnValue(\React\Promise\reject()));

        $stream = (new $className($path, $fd, $filesystem));
        $stream->close();
        $stream->close();
    }
}
