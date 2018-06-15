<?php

namespace React\Tests\Filesystem\ChildProcess;

use React\ChildProcess\Process;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\FulfilledPromise;
use WyriHaximus\React\ChildProcess\Pool\PoolFactoryInterface;
use WyriHaximus\React\ChildProcess\Pool\ProcessCollection\Single;

final class PoolRpcErrorMockFactory implements PoolFactoryInterface
{
    public static function create(Process $childProcess, LoopInterface $loop, array $options = [])
    {
        return new FulfilledPromise(new PoolRpcErrorMock(
            new Single(function () {}),
            Factory::create(),
            []
        ));
    }

    public static function createFromClass($class, LoopInterface $loop, array $options = [])
    {
        return new FulfilledPromise(new PoolRpcErrorMock(
            new Single(function () {}),
            Factory::create(),
            []
        ));
    }

}
