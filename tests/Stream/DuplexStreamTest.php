<?php

namespace React\Tests\Filesystem\Stream;

use React\Filesystem\Stream\DuplexStream;
use React\Tests\Filesystem\TestCase;

class DuplexStreamTest extends TestCase
{
    /**
     * @expectedException Exception
     */
    public function testPipe()
    {
        $path = 'foo.bar';
        $fileDescriptor = '0123456789abcdef';

        $filesystem = $this->mockAdapter();

        $stream = new DuplexStream($path, $fileDescriptor, $filesystem);

        $this->assertSame($stream, $stream->pipe($stream));
    }
}
