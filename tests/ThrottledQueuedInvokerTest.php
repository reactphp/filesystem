<?php

namespace React\Tests\Filesystem;

use React\Filesystem\ThrottledQueuedInvoker;

class ThrottledQueuedInvokerTest extends TestCase
{
    public function testInvokeCall()
    {
        $function = [
            'foo',
            'bar',
            'baz',
        ];

        $args = [
            [
                'bar',
                'baz',
            ],
            [
                'baz',
                'foo',
            ],
            [
                'foo',
                'bar',
            ],
        ];

        $errorResultCode = [
            13,
            14,
            42,
        ];

        $filesystem = $this->mockAdapter();

        foreach ($function as $key => $value) {
            $filesystem
                ->expects($this->at($key + 1))
                ->method('callFilesystem')
                ->with($function[$key], $args[$key], $errorResultCode[$key])
                ->will($this->returnValue(\React\Promise\resolve()));
        }

        $invoker = new ThrottledQueuedInvoker($filesystem);
        $this->assertTrue($invoker->isEmpty());
        foreach ($function as $key => $value) {
            $invoker->invokeCall($function[$key], $args[$key], $errorResultCode[$key]);
        }
        $this->assertFalse($invoker->isEmpty());

        $this->loop->run();
        $this->assertTrue($invoker->isEmpty());
    }

    public function testInterval()
    {
        $invoker = new ThrottledQueuedInvoker($this->mockAdapter());
        $this->assertSame(ThrottledQueuedInvoker::DEFAULT_INTERVAL, $invoker->getInterval());

        $invoker = new ThrottledQueuedInvoker($this->mockAdapter(), 1.3);
        $this->assertSame(1.3, $invoker->getInterval());

        $invoker->setInterval(3.2);
        $this->assertSame(3.2, $invoker->getInterval());
    }
}
