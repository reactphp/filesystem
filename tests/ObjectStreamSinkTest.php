<?php

namespace React\Tests\Filesystem;

use SplObjectStorage;
use React\Filesystem\Filesystem;
use React\Filesystem\ObjectStream;
use React\Filesystem\ObjectStreamSink;
use React\Promise\PromiseInterface;

class ObjectStreamSinkTest extends TestCase
{
    public function testSink()
    {
        $adapter = $this->mockAdapter();
        $fs = Filesystem::createFromAdapter($adapter);

        $node = $fs->file('test.foo.bar');
        $stream = new ObjectStream();

        $sink = ObjectStreamSink::promise($stream);
        $this->assertInstanceOf(PromiseInterface::class, $sink);

        $stream->emit('data', [$node]);
        $stream->close();

        $nodes = null;
        $sink->then(function (SplObjectStorage $list) use (&$nodes) {
            $nodes = $list;
        });
        $nodes->rewind();

        $this->assertSame(1, $nodes->count());
        $this->assertSame($node, $nodes->current());
    }
}
