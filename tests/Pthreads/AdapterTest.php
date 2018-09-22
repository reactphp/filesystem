<?php

namespace React\Tests\Filesystem\Pthreads;

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
        $this->adapter = new Adapter($this->loop, [ 'workers' => [ 'size' => 5, 'timerInterval' => 0.01 ] ]);
        $this->filesystem = Filesystem::createFromAdapter($this->adapter);
    }

    public function tearDown()
    {
        $this->adapter->destroy();
        parent::tearDown();
    }
}
