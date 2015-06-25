<?php

namespace React\Filesystem\Node;


use React\Promise\Deferred;
use React\Stream\ReadableStreamInterface;

class StreamSink
{
    /**
     * @param ReadableStreamInterface $stream
     * @return \React\Promise\Promise
     */
    public static function promise(ReadableStreamInterface $stream)
    {
        $deferred = new Deferred();
        $list = new \SplObjectStorage();

        $stream->on('data', function (NodeInterface $node) use ($list) {
            $list->attach($node);
        });
        $stream->on('end', function () use ($deferred, $list) {
            $deferred->resolve($list);
        });

        return $deferred->promise();
    }
}
