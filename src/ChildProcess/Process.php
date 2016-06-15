<?php

namespace React\Filesystem\ChildProcess;

use React\EventLoop\LoopInterface;
use React\Filesystem\WoolTrait;
use WyriHaximus\React\ChildProcess\Messenger\ChildInterface;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Payload;
use WyriHaximus\React\ChildProcess\Messenger\Messenger;

class Process implements ChildInterface
{
    use WoolTrait;

    /**
     * @inheritDoc
     */
    public static function create(Messenger $messenger, LoopInterface $loop)
    {
        return new self($messenger);
    }

    /**
     * Process constructor.
     * @param Messenger $messenger
     */
    public function __construct(Messenger $messenger)
    {
        foreach ([
            'mkdir',
            'rmdir',
            'unlink',
            'chmod',
            'chown',
            'stat',
            'readdir',
            'touch',
            'open',
            'read',
            'write',
            'close',
            'rename',
            'readlink',
            'symlink',
        ] as $method) {
            $messenger->registerRpc($method, $this->wrapper($method));
        }
    }

    protected function wrapper($function)
    {
        return function (Payload $payload, Messenger $messenger) use ($function) {
            return $this->$function($payload->getPayload());
        };
    }
}
