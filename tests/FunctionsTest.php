<?php

namespace React\Tests\Filesystem;

use React\Filesystem\QueuedInvoker;
use React\Filesystem\InstantInvoker;
use React\Filesystem\OpenFileLimiter;

class FunctionsTest extends TestCase
{
    public function testGetInvoker()
    {
        $adapter = $this->mockAdapter();
        $invoker = new QueuedInvoker($adapter);

        $options = [
            'k' => $invoker,
        ];

        $callInvoker = \React\Filesystem\getInvoker($adapter, $options, 'k', InstantInvoker::class);
        $this->assertSame($invoker, $callInvoker);

        $callInvoker2 = \React\Filesystem\getInvoker($adapter, $options, 'l', InstantInvoker::class);
        $this->assertInstanceOf(InstantInvoker::class, $callInvoker2);
    }

    public function testGetInvokerFallback()
    {
        $adapter = $this->mockAdapter();
        $fallback = \stdClass::class;
        $this->assertInstanceOf($fallback, \React\Filesystem\getInvoker($adapter, [], 'k', $fallback));
    }

    public function testGetOpenFileLimit()
    {
        $limit = 123;
        $this->assertSame($limit, \React\Filesystem\getOpenFileLimit([
            'open_file_limit' => $limit,
        ]));
    }

    public function testGetOpenFileLimitFallback()
    {
        $this->assertSame(OpenFileLimiter::DEFAULT_LIMIT, \React\Filesystem\getOpenFileLimit([]));
    }
}
