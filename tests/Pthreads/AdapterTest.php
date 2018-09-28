<?php

namespace React\Tests\Filesystem\Pthreads;

use CharlotteDunois\Phoebe\Pool;
use CharlotteDunois\Phoebe\Worker;
use React\Filesystem\Filesystem;
use React\Filesystem\Pthreads\Adapter;
use React\Tests\Filesystem\AdapterTestAbstract;

/**
 * @requires extension pthreads
 */
class AdapterTest extends AdapterTestAbstract
{
    public function setUp()
    {
        parent::setUp();

        Worker::$workerInterval = 0.001;
        $this->adapter = new Adapter($this->loop, [ 'workers' => [ 'size' => 1, 'timerInterval' => 0.01 ] ]);
        $this->filesystem = Filesystem::createFromAdapter($this->adapter);
    }

    public function tearDown()
    {
        $this->await($this->adapter->destroy(), $this->adapter->getLoop());
        parent::tearDown();
    }

    public function testGetPool()
    {
        $this->assertInstanceOf(Pool::class, $this->adapter->getPool());
    }
}