<?php

namespace React\Tests\Filesystem\Eio;

use React\Filesystem\Filesystem;
use React\Filesystem\Eio\Adapter;
use React\Tests\Filesystem\AdapterTestAbstract;

/**
 * @requires extension eio
 */
class AdapterTest extends AdapterTestAbstract
{
    public function setUp()
    {
        parent::setUp();

        $this->adapter = new Adapter($this->loop);
        $this->filesystem = Filesystem::createFromAdapter($this->adapter);
    }
}
