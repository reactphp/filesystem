<?php

namespace React\Tests\Filesystem\ChildProcess;

use React\Filesystem\Filesystem;
use React\Filesystem\ChildProcess\Adapter;
use React\Tests\Filesystem\AdapterTestAbstract;

class AdapterTest extends AdapterTestAbstract
{
    public function setUp()
    {
        if (!Adapter::isSupported()) {
            return $this->markTestSkipped('Unsupported adapter');
        }

        parent::setUp();

        $this->adapter = new Adapter($this->loop);
        $this->filesystem = Filesystem::createFromAdapter($this->adapter);
    }
}
