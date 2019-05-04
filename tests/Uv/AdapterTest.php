<?php

namespace React\Tests\Filesystem\Uv;

use React\EventLoop\Factory;
use React\Filesystem\Filesystem;
use React\Filesystem\PermissionFlagResolver;
use React\Filesystem\Uv\Adapter;
use React\Promise\FulfilledPromise;
use React\Tests\Filesystem\CallInvokerProvider;
use React\Tests\Filesystem\AdapterTestAbstract;

/**
 * @requires extension uv
 */
class AdapterTest extends AdapterTestAbstract
{
    public function createAdapter()
    {
        return (new Adapter($this->loop));
    }

    public function testStat()
    {
        if (\DIRECTORY_SEPARATOR === '\\') {
            return $this->markTestSkipped('Unsupported on Windows (different dev, rdev and ino)');
        }

        parent::testStat();
    }
}
