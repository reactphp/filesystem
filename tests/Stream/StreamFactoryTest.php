<?php

namespace React\Tests\Filesystem\Stream;

use React\Filesystem\Stream\StreamFactory;

class StreamFactoryTest extends \PHPUnit_Framework_TestCase
{

    public function testCreateRead()
    {
        $filesystem = $this->getMock('React\Filesystem\Eio\Adapter', [
            'stat',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with('foo.bar')
            ->will($this->returnValue($this->getMock('React\Promise\PromiseInterface')))
        ;

        $this->assertInstanceOf(
            'React\Filesystem\Stream\ReadableStream',
            StreamFactory::create('foo.bar', null, EIO_O_RDONLY, $filesystem)
        );
    }

    public function testCreateWrite()
    {
        $filesystem = $this->getMock('React\Filesystem\Eio\Adapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $this->assertInstanceOf(
            'React\Filesystem\Stream\WritableStream',
            StreamFactory::create('foo.bar', null, EIO_O_WRONLY, $filesystem)
        );
    }

    public function testCreateDuplex()
    {
        $filesystem = $this->getMock('React\Filesystem\Eio\Adapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $this->assertInstanceOf(
            'React\Filesystem\Stream\DuplexStream',
            StreamFactory::create('foo.bar', null, EIO_O_RDWR, $filesystem)
        );
    }
}
