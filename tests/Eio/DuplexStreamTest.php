<?php

namespace React\Tests\Filesystem\Stream;

use React\Filesystem\Eio\DuplexStream;

class DuplexStreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Exception
     */
    public function testPipe()
    {
        $path = 'foo.bar';
        $fileDescriptor = '0123456789abcdef';

        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'read',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $stream = new DuplexStream($path, $fileDescriptor, $filesystem);

        $this->assertSame($stream, $stream->pipe($stream));
    }
}
