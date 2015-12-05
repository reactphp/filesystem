<?php

namespace React\Tests\Filesystem;

use React\EventLoop\Factory;
use React\Filesystem\PooledInvoker;
use React\Filesystem\QueuedInvoker;
use React\Promise\FulfilledPromise;

class PooledInvokerTest extends TestCase
{
    public function testInvokeCall()
    {
        $loop = Factory::create();

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

        $filesystem = $this->mockAdapter($loop);

        foreach ($function as $key => $value) {
            $filesystem
                ->expects($this->at($key + 1))
                ->method('callFilesystem')
                ->with($function[$key], $args[$key], $errorResultCode[$key])
                ->will($this->returnValue(new FulfilledPromise()))
            ;
        }

        $invoker = new PooledInvoker($filesystem);
        $this->assertTrue($invoker->isEmpty());
        foreach ($function as $key => $value) {
            $invoker->invokeCall($function[$key], $args[$key], $errorResultCode[$key]);
        }
        $this->assertFalse($invoker->isEmpty());

        $loop->run();
        $this->assertTrue($invoker->isEmpty());
    }
}
