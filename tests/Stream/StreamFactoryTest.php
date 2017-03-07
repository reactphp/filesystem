<?php

namespace React\Tests\Filesystem\Stream;

use React\Filesystem\Stream\StreamFactory;
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
            ->will($this->returnValue(\React\Promise\resolve([])))
        ;

        $this->assertInstanceOf(
            'React\Filesystem\Stream\ReadableStream',
            StreamFactory::create('foo.bar', null, 'r', $filesystem)
        );
    }

    public function testCreateWrite()
    {
        $filesystem = $this->mockAdapter();

        $this->assertInstanceOf(
            'React\Filesystem\Stream\WritableStream',
            StreamFactory::create('foo.bar', null, 'w', $filesystem)
        );
    }

    public function testCreateDuplex()
    {
        $filesystem = $this->mockAdapter();

        $this->assertInstanceOf(
            'React\Filesystem\Stream\DuplexStream',
            StreamFactory::create('foo.bar', null, '+', $filesystem)
        );
    }
}
