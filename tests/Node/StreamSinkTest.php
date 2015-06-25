<?php

namespace React\Tests\Filesystem\Node;

use React\Filesystem\Node\File;
use React\Filesystem\Node\Stream;
use React\Filesystem\Node\StreamSink;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;

class StreamSinkTest extends \PHPUnit_Framework_TestCase
{
    public function testSink()
    {
        $node = $this->getMock('React\Filesystem\Node\NodeInterface');
        $stream = new Stream();
        $sink = StreamSink::promise($stream);
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
