<?php

namespace React\Tests\Filesystem;

use React\Filesystem\Node\NodeInterface;
use React\Filesystem\ObjectStream;

class ObjectStreamTest extends TestCase
{
    public function testObjectStream()
    {
        $node = $this->getMock('React\Filesystem\Node\NodeInterface');
        $stream = new ObjectStream();

        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isReadable());

        $stream->on('data', function (NodeInterface $data) use ($node) {
            $this->assertEquals($node, $data);
        });
        $stream->end($node);

        $this->assertFalse($stream->isWritable());
        $this->assertFalse($stream->isReadable());

        $stream->close();

        $this->assertFalse($stream->isWritable());
        $this->assertFalse($stream->isReadable());
    }
}
