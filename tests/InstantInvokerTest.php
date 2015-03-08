<?php

namespace React\Tests\Filesystem;

use React\Filesystem\InstantInvoker;

class InstantInvokerTest extends \PHPUnit_Framework_TestCase
{
    public function testInvokeCall()
    {
        $function = 'foo';
        $args = [
            'bar',
            'baz',
        ];
        $errorResultCode = 13;

        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'callFilesystem',
        ], [
            $this->getMock('React\EventLoop\LoopInterface'),
        ]);


        $filesystem
            ->expects($this->once())
            ->method('callFilesystem')
            ->with($function, $args, $errorResultCode)
        ;


        $invoker = new InstantInvoker($filesystem);
        $invoker->invokeCall($function, $args, $errorResultCode);
    }
}
