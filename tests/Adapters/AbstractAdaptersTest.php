<?php

namespace React\Tests\Filesystem\Adapters;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Filesystem\ChildProcess;
use React\Filesystem\Eio;
use React\Filesystem\Filesystem;
use React\Filesystem\Pthreads;
use React\Tests\Filesystem\TestCase;

abstract class AbstractAdaptersTest extends TestCase
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    public function adapterProvider()
    {
        $adapters = [];
        $adapters['child-process'] = $this->getChildProcessProvider();

        if (extension_loaded('eio')) {
            $adapters['eio'] = $this->getEioProvider();
        }

        if (extension_loaded('pthreads')) {
            $adapters['pthreads'] = $this->getPthreadsProvider();
        }

        return $adapters;
    }

    protected function getChildProcessProvider()
    {
        $loop = Factory::create();
        return [
            $loop,
            new ChildProcess\Adapter($loop),
        ];
    }

    protected function getEioProvider()
    {
        $loop = Factory::create();
        return [
            $loop,
            new Eio\Adapter($loop),
        ];
    }

    protected function getPthreadsProvider()
    {
        $loop = Factory::create();
        return [
            $loop,
            new Pthreads\Adapter($loop),
        ];
    }

    public function filesystemProvider()
    {
        $filesystems = [];

        foreach ($this->adapterProvider() as $name => $adapter) {
            $filesystems[$name] = [
                $adapter[0],
                Filesystem::createFromAdapter($adapter[1]),
            ];
        }

        return $filesystems;
    }
}
