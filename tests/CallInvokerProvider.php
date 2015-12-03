<?php

namespace React\Tests\Filesystem;

use React\EventLoop\Factory;
use React\Filesystem\InstantInvoker;
use React\Filesystem\PooledInvoker;
use React\Filesystem\QueuedInvoker;
use React\Filesystem\ThrottledQueuedInvoker;

class CallInvokerProvider extends TestCase
{
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
        $adapter = $this->mockAdapter($loop);
        $invoker = new PooledInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$loop, $adapter, $invoker];
    }

    protected function instant()
    {
        $loop = Factory::create();
        $adapter = $this->mockAdapter($loop);
        $invoker = new InstantInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$loop, $adapter, $invoker];
    }

    protected function queued()
    {
        $loop = Factory::create();
        $adapter = $this->mockAdapter($loop);
        $invoker = new QueuedInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$loop, $adapter, $invoker];
    }

    protected function throttledqueued()
    {
        $loop = Factory::create();
        $adapter = $this->mockAdapter($loop);
        $invoker = new ThrottledQueuedInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$loop, $adapter, $invoker];
    }
}
