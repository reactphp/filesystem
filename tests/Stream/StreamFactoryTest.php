<?php

namespace React\Tests\Filesystem\Stream;

use InvalidArgumentException;

use React\Filesystem\Stream\DuplexStream;
use React\Filesystem\Stream\StreamFactory;
use React\Filesystem\Stream\ReadableStream;
use React\Filesystem\Stream\WritableStream;
use React\Tests\Filesystem\TestCase;

class StreamFactoryTest extends TestCase
{
    public function testCreateRead()
    {
        $filesystem = $this->mockAdapter();
        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with('foo.bar')
            ->will($this->returnValue(\React\Promise\resolve([])));

        $this->assertInstanceOf(
            ReadableStream::class,
            StreamFactory::create('foo.bar', null, 'r', $filesystem)
        );
    }

    public function testCreateWrite()
    {
        $filesystem = $this->mockAdapter();

        $this->assertInstanceOf(
            WritableStream::class,
            StreamFactory::create('foo.bar', null, 'w', $filesystem)
        );
    }

    public function testCreateDuplex()
    {
        $filesystem = $this->mockAdapter();

        $this->assertInstanceOf(
            DuplexStream::class,
            StreamFactory::create('foo.bar', null, '+', $filesystem)
        );
    }

    public function testUnknownFlag()
    {
        $filesystem = $this->mockAdapter();

        $this->expectException(InvalidArgumentException::class);
        StreamFactory::create('foo.bar', null, 'u', $filesystem);
    }
}
