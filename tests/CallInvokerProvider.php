<?php

namespace React\Tests\Filesystem;

use React\EventLoop\Factory;
use React\Filesystem\InstantInvoker;
use React\Filesystem\PooledInvoker;
use React\Filesystem\QueuedInvoker;
use React\Filesystem\ThrottledQueuedInvoker;

class CallInvokerProvider extends \PHPUnit_Framework_TestCase
{
    protected $mockedMethods = [
        'executeDelayedCall',
        'callFilesystem',
        'close',
    ];

    public function callInvokerProvider()
    {
        return [
            'pooled' => $this->pooled(),
            'instant' => $this->instant(),
            'queued' => $this->queued(),
            'throttledqueued' => $this->throttledqueued(),
        ];
    }

    protected function pooled()
    {
        $loop = Factory::create();
        $adapter = $this->getMock('React\Filesystem\EioAdapter', $this->mockedMethods, [
            $loop,
        ]);
        $invoker = new PooledInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$loop, $adapter, $invoker];
    }

    protected function instant()
    {
        $loop = Factory::create();
        $adapter = $this->getMock('React\Filesystem\EioAdapter', $this->mockedMethods, [
            $loop,
        ]);
        $invoker = new InstantInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$loop, $adapter, $invoker];
    }

    protected function queued()
    {
        $loop = Factory::create();
        $adapter = $this->getMock('React\Filesystem\EioAdapter', $this->mockedMethods, [
            $loop,
        ]);
        $invoker = new QueuedInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$loop, $adapter, $invoker];
    }

    protected function throttledqueued()
    {
        $loop = Factory::create();
        $adapter = $this->getMock('React\Filesystem\EioAdapter', $this->mockedMethods, [
            $loop,
        ]);
        $invoker = new ThrottledQueuedInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$loop, $adapter, $invoker];
    }
}
