<?php

namespace React\Tests\Filesystem;

use React\EventLoop\Factory;
use React\Filesystem\PooledInvoker;
use React\Filesystem\QueuedInvoker;
use React\Promise\FulfilledPromise;

class PooledInvokerTest extends \PHPUnit_Framework_TestCase
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


        $invoker = new PooledInvoker($filesystem);
        foreach ($function as $key => $value) {
            $invoker->invokeCall($function[$key], $args[$key], $errorResultCode[$key]);
        }

        $loop->run();
    }
}
