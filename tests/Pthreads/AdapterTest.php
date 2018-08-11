<?php

namespace React\Tests\Filesystem\Pthreads;

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

        $this->adapter = new Adapter($this->loop);
        $this->filesystem = Filesystem::createFromAdapter($this->adapter);
    }

    public function tearDown()
    {
        $this->adapter->destroy();
        parent::tearDown();
    }
}
