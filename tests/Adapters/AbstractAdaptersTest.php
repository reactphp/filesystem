<?php

namespace React\Tests\Filesystem\Adapters;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Filesystem\ChildProcess;
use React\Filesystem\Eio;
use React\Filesystem\Pthreads;
use React\Tests\Filesystem\TestCase;

abstract class AbstractAdaptersTest extends TestCase
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->loop = Factory::create();
    }

    public function tearDown()
    {
        $this->loop = Factory::create();
        parent::tearDown();
    }

    public function adapterProvider()
    {
        $adapters = [];
        $adapters['child-process'] = [
            new ChildProcess\Adapter($this->loop),
        ];

        if (extension_loaded('eio')) {
            $adapters['eio'] = [
                new Eio\Adapter($this->loop),
            ];
        }

        if (extension_loaded('pthreads')) {
            $adapters['pthreads'] = [
                new Pthreads\Adapter($this->loop),
            ];
        }

        return $adapters;
    }
}
