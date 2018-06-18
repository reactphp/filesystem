<?php

namespace React\Tests\Filesystem\ChildProcess;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Message;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Rpc;
use WyriHaximus\React\ChildProcess\Pool\PoolInterface;
use WyriHaximus\React\ChildProcess\Pool\ProcessCollectionInterface;

final class PoolRpcErrorMock extends EventEmitter implements PoolInterface
{
    public function __construct(ProcessCollectionInterface $processCollection, LoopInterface $loop, array $options = [])
    {
        // void
    }

    public function rpc(Rpc $message)
    {
        return new RejectedPromise([
            'error' => [
                'message' => 'oops',
            ],
        ]);
    }

    public function message(Message $message)
    {
        return new FulfilledPromise();
    }

    public function terminate(Message $message, $timeout = 5, $signal = null)
    {
        return new FulfilledPromise();
    }

    public function info()
    {
        return [];
    }
}
