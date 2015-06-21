<?php

namespace React\Tests\Filesystem;

use React\EventLoop\Factory;
use React\Filesystem\QueuedInvoker;
use React\Promise\FulfilledPromise;

class QueuedInvokerTest extends \PHPUnit_Framework_TestCase
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


        $invoker = new QueuedInvoker($filesystem);
        $this->assertTrue($invoker->isEmpty());
        foreach ($function as $key => $value) {
            $invoker->invokeCall($function[$key], $args[$key], $errorResultCode[$key]);
        }
        $this->assertFalse($invoker->isEmpty());

        $loop->run();
        $this->assertTrue($invoker->isEmpty());
    }
}
