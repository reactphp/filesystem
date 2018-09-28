<?php

namespace React\Tests\Filesystem\Stream;

use Exception;
use React\Filesystem\Stream\DuplexStream;
use React\Tests\Filesystem\TestCase;

class DuplexStreamTest extends TestCase
{
    public function testPipe()
    {
        $path = 'foo.bar';
        $fileDescriptor = '0123456789abcdef';

        $filesystem = $this->mockAdapter();

        $stream = new DuplexStream($path, $fileDescriptor, $filesystem);

        $this->expectException(Exception::class);
        $this->assertSame($stream, $stream->pipe($stream));
    }
}
