<?php

namespace React\Tests\Filesystem\Eio;

use React\Filesystem\Eio\StreamFactory;

class StreamFactoryTest extends \PHPUnit_Framework_TestCase
{

    public function testCreateRead()
    {
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
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

        $this->assertInstanceOf('React\Filesystem\Eio\ReadableStream', StreamFactory::create('foo.bar', null, EIO_O_RDONLY, $filesystem));
    }

    public function testCreateWrite()
    {
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $this->assertInstanceOf('React\Filesystem\Eio\WritableStream', StreamFactory::create('foo.bar', null, EIO_O_WRONLY, $filesystem));
    }
}
