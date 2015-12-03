<?php

namespace React\Tests\Filesystem;

use React\Filesystem\InstantInvoker;

class InstantInvokerTest extends TestCase
{
    public function testInvokeCall()
    {
        $function = 'foo';
        $args = [
            'bar',
            'baz',
        ];
        $errorResultCode = 13;

        $filesystem = $this->mockAdapter();


        $filesystem
            ->expects($this->once())
            ->method('callFilesystem')
            ->with($function, $args, $errorResultCode)
        ;


        $invoker = new InstantInvoker($filesystem);
        $this->assertTrue($invoker->isEmpty());
        $invoker->invokeCall($function, $args, $errorResultCode);
        $this->assertTrue($invoker->isEmpty());
    }
}
