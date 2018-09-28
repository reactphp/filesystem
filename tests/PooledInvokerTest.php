<?php

namespace React\Tests\Filesystem;

use React\Filesystem\PooledInvoker;

class PooledInvokerTest extends TestCase
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

        $invoker = new PooledInvoker($filesystem);
        $this->assertTrue($invoker->isEmpty());
        foreach ($function as $key => $value) {
            $invoker->invokeCall($function[$key], $args[$key], $errorResultCode[$key]);
        }
        $this->assertFalse($invoker->isEmpty());

        $this->loop->run();
        $this->assertTrue($invoker->isEmpty());
    }
}
