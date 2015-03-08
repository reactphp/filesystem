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
            1 => 'foo',
        ];
        $args = [
            1 => [
                'bar',
                'baz',
            ],
        ];
        $errorResultCode = [
            1 => 13,
        ];

        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'callFilesystem',
        ], [
            $loop,
        ]);


        $filesystem
            ->expects($this->any())
            ->method('callFilesystem')
            ->with($function[1], $args[1], $errorResultCode[1])
            ->will($this->returnValue(new FulfilledPromise(time())))
        ;


        $invoker = new QueuedInvoker($filesystem);
        $invoker->invokeCall($function, $args, $errorResultCode);

        $loop->run();
    }
}
