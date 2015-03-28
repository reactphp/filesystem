<?php

namespace React\Tests\Filesystem;

use React\EventLoop\Factory;
use React\Filesystem\InstantInvoker;
use React\Filesystem\PooledInvoker;
use React\Filesystem\QueuedInvoker;
use React\Filesystem\ThrottledQueuedInvoker;

class CallInvokerProvider extends \PHPUnit_Framework_TestCase
{
    public function callInvokerProvider()
    {
        $mockedMethods = [
            'executeDelayedCall',
            'callFilesystem',
            'close',
        ];

        $invokers = [];

        $invokers['pooled'][0] = Factory::create();
        $invokers['pooled'][1] = $this->getMock('React\Filesystem\EioAdapter', $mockedMethods, [
            $invokers['pooled'][0],
        ]);
        $invokers['pooled'][2] = new PooledInvoker($invokers['pooled'][1]);
        $invokers['pooled'][1]->setInvoker($invokers['pooled'][2]);

        $invokers['instant'][0] = Factory::create();
        $invokers['instant'][1] = $this->getMock('React\Filesystem\EioAdapter', $mockedMethods, [
            $invokers['instant'][0],
        ]);
        $invokers['instant'][2] = new InstantInvoker($invokers['instant'][1]);
        $invokers['instant'][1]->setInvoker($invokers['instant'][2]);

        $invokers['queued'][0] = Factory::create();
        $invokers['queued'][1] = $this->getMock('React\Filesystem\EioAdapter', $mockedMethods, [
            $invokers['queued'][0],
        ]);
        $invokers['queued'][2] = new QueuedInvoker($invokers['pooled'][1]);
        $invokers['queued'][1]->setInvoker($invokers['queued'][2]);

        $invokers['throttledqueued'][0] = Factory::create();
        $invokers['throttledqueued'][1] = $this->getMock('React\Filesystem\EioAdapter', $mockedMethods, [
            $invokers['throttledqueued'][0],
        ]);
        $invokers['throttledqueued'][2] = new ThrottledQueuedInvoker($invokers['throttledqueued'][1]);
        $invokers['throttledqueued'][1]->setInvoker($invokers['throttledqueued'][2]);

        return $invokers;
    }
}
