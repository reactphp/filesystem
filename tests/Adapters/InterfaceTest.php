<?php

namespace React\Tests\Filesystem\Adapters;

use React\Filesystem\AdapterInterface;
use React\Filesystem\ChildProcess;
use React\Filesystem\Eio;
use React\Filesystem\Pthreads;

class InterfaceTest extends AbstractAdaptersTest
{
    /**
     * @dataProvider adapterProvider
     */
    public function testInterface(AdapterInterface $adapter)
    {
        $this->assertInstanceOf('React\Filesystem\AdapterInterface', $adapter);
    }

    /**
     * @dataProvider adapterProvider
     */
    public function testLoop(AdapterInterface $adapter)
    {
        $this->assertInstanceOf('React\EventLoop\LoopInterface', $adapter->getLoop());
    }
}
