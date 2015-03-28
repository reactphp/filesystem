<?php

namespace React\Tests\Filesystem;

use React\EventLoop\Factory;
use React\Filesystem\ThrottledQueuedInvoker;
use React\Promise\FulfilledPromise;

class ThrottledQueuedInvokerTest extends \PHPUnit_Framework_TestCase
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

        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'callFilesystem',
        ], [
            $loop,
        ]);

        foreach ($function as $key => $value) {
            $filesystem
                ->expects($this->at($key))
                ->method('callFilesystem')
                ->with($function[$key], $args[$key], $errorResultCode[$key])
                ->will($this->returnValue(new FulfilledPromise()))
            ;
        }

        $invoker = new ThrottledQueuedInvoker($filesystem);
        foreach ($function as $key => $value) {
            $invoker->invokeCall($function[$key], $args[$key], $errorResultCode[$key]);
        }

        $loop->run();
    }

    public function testInterval()
    {
        $invoker = new ThrottledQueuedInvoker($this->getMock('React\Filesystem\EioAdapter', [], [
            Factory::create(),
        ]));
        $this->assertSame(ThrottledQueuedInvoker::DEFAULT_INTERVAL, $invoker->getInterval());
        $invoker = new ThrottledQueuedInvoker($this->getMock('React\Filesystem\EioAdapter', [], [
            Factory::create(),
        ]), 1.3);
        $this->assertSame(1.3, $invoker->getInterval());
        $invoker->setInterval(3.2);
        $this->assertSame(3.2, $invoker->getInterval());
    }
}
