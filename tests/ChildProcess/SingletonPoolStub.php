<?php

namespace React\Tests\Filesystem\ChildProcess;

use Evenement\EventEmitter;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Promise\FulfilledPromise;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Message;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Rpc;
use WyriHaximus\React\ChildProcess\Pool\PoolFactoryInterface;
use WyriHaximus\React\ChildProcess\Pool\PoolInterface;
use WyriHaximus\React\ChildProcess\Pool\ProcessCollection\Single;
use WyriHaximus\React\ChildProcess\Pool\ProcessCollectionInterface;

final class SingletonPoolStub extends EventEmitter implements PoolInterface, PoolFactoryInterface
{
    private static $calls = [];
    private static $instance = null;
    private static $rpcResponse = null;

    public static function createFromClass($className, LoopInterface $loop, array $options = [])
    {
        if (self::$instance == null)
        {
            self::$instance = new SingletonPoolStub(
                new Single(function () {}),
                $loop,
                $options
            );
        }

        return new FulfilledPromise(self::$instance);
    }

    public static function reset()
    {
        self::$calls = [];
    }

    public static function getCalls()
    {
        return self::$calls;
    }

    public function info()
    {
        self::$calls[] = [__FUNCTION__, func_get_args()];
    }

    public function __construct(ProcessCollectionInterface $processCollection, LoopInterface $loop, array $options = [])
    {
    }

    public function rpc(Rpc $message)
    {
        self::$calls[] = [__FUNCTION__, func_get_args()];
        return \React\Promise\resolve(self::$rpcResponse);
    }

    public static function setRpcResponse($response)
    {
        self::$rpcResponse = $response;
    }

    public function message(Message $message)
    {
        self::$calls[] = [__FUNCTION__, func_get_args()];
    }

    public function terminate(Message $message, $timeout = 5, $signal = null)
    {
        self::$calls[] = [__FUNCTION__, func_get_args()];
    }

    public static function create(Process $childProcess, LoopInterface $loop, array $options = [])
    {
        // TODO: Implement create() method.
    }
}
