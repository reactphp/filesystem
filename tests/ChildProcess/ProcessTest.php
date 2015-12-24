<?php

namespace React\Tests\Filesystem\ChildProcess;

use React\Filesystem\ChildProcess\Process;
use React\Tests\Filesystem\TestCase;

class ProcessTest extends TestCase
{
    public function testConstruct()
    {
        $messenger = $this->getMockBuilder('WyriHaximus\React\ChildProcess\Messenger\Messenger')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $messenger
            ->expects($this->atLeastOnce())
            ->method('registerRpc')
        ;

        new Process($messenger);
    }
}
