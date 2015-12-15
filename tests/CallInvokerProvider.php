<?php

namespace React\Tests\Filesystem;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\InstantInvoker;
use React\Filesystem\PooledInvoker;
use React\Filesystem\QueuedInvoker;
use React\Filesystem\ThrottledQueuedInvoker;

class CallInvokerProvider extends TestCase
{
    public function callInvokerProvider($loop = null, $adapter = null)
    {
        if (!($loop instanceof LoopInterface)) {
            $loop = Factory::create();
        }

        if (!($adapter instanceof AdapterInterface)) {
            $adapter = $this->mockAdapter($loop);
        }

        return [
            'pooled' => $this->pooled($loop, $adapter),
            'instant' => $this->instant($loop, $adapter),
            'queued' => $this->queued($loop, $adapter),
            'throttledqueued' => $this->throttledqueued($loop, $adapter),
        ];
    }

    protected function pooled($loop, $adapter)
    {
        $invoker = new PooledInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$loop, $adapter, $invoker];
    }

    protected function instant($loop, $adapter)
    {
        $invoker = new InstantInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$loop, $adapter, $invoker];
    }

    protected function queued($loop, $adapter)
    {
        $invoker = new QueuedInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$loop, $adapter, $invoker];
    }

    protected function throttledqueued($loop, $adapter)
    {
        $invoker = new ThrottledQueuedInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$loop, $adapter, $invoker];
    }
}
