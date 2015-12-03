<?php

namespace React\Tests\Filesystem;

use React\Filesystem\OpenFileLimiter;

class OpenFileLimiterTest extends TestCase
{
    public function testBasic()
    {
        $limiter = new OpenFileLimiter(1);
        $this->assertSame(1, $limiter->getLimit());
        $this->assertSame(0, $limiter->getOutstanding());
        $this->assertSame(0, $limiter->getQueueSize());

        $promise1 = $limiter->open();
        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise1);
        $promiseCallbackCalled1 = false;
        $promise1->then(function () use (&$promiseCallbackCalled1) {
            $promiseCallbackCalled1 = true;
        });
        $this->assertSame(1, $limiter->getOutstanding());
        $this->assertSame(0, $limiter->getQueueSize());

        $promise2 = $limiter->open();
        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise2);
        $promiseCallbackCalled2 = false;
        $promise2->then(function () use (&$promiseCallbackCalled2) {
            $promiseCallbackCalled2 = true;
        });
        $this->assertSame(1, $limiter->getOutstanding());
        $this->assertSame(1, $limiter->getQueueSize());

        $limiter->close();
        $this->assertSame(1, $limiter->getOutstanding());
        $this->assertSame(0, $limiter->getQueueSize());
        $this->assertTrue($promiseCallbackCalled1);

        $limiter->close();
        $this->assertSame(0, $limiter->getOutstanding());
        $this->assertSame(0, $limiter->getQueueSize());
        $this->assertTrue($promiseCallbackCalled2);
    }
}
