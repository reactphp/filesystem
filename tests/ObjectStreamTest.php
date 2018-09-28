<?php

namespace React\Tests\Filesystem;

use React\Filesystem\Filesystem;
use React\Filesystem\Node\NodeInterface;
use React\Filesystem\ObjectStream;

class ObjectStreamTest extends TestCase
{
    public function testObjectStream()
    {
        $adapter = $this->mockAdapter();
        $fs = Filesystem::createFromAdapter($adapter);

        $node = $fs->file('test.foo.bar');
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

        $stream = new ObjectStream();
        $dest = new ObjectStream();

        $stream->pipe($dest);
        $node = $fs->file('test.foo.bar');

        $dest->on('data', function (NodeInterface $data) use ($node) {
            $this->assertEquals($node, $data);
        });

        $stream->end($node);

        $stream->pause();
        $stream->resume();

        $stream->close();
        $dest->close();
    }
}
