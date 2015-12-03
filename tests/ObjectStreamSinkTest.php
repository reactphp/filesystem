<?php

namespace React\Tests\Filesystem;

use React\Filesystem\ObjectStream;
use React\Filesystem\ObjectStreamSink;

class ObjectStreamSinkTest extends TestCase
{
    public function testSink()
    {
        $node = $this->getMock('React\Filesystem\Node\NodeInterface');
        $stream = new ObjectStream();
        $sink = ObjectStreamSink::promise($stream);
        $this->assertInstanceOf('React\Promise\PromiseInterface', $sink);
        $stream->emit('data', [$node]);
        $stream->close();
        $nodes = null;
        $sink->then(function (\SplObjectStorage $list) use (&$nodes) {
            $nodes = $list;
        });
        $nodes->rewind();
        $this->assertSame(1, $nodes->count());
        $this->assertSame($node, $nodes->current());
    }
}
