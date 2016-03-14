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
        $messenger->registerRpc('mkdir',    $this->wrapper('mkdir'));
        $messenger->registerRpc('rmdir',    $this->wrapper('rmdir'));
        $messenger->registerRpc('unlink',   $this->wrapper('unlink'));
        $messenger->registerRpc('chmod',    $this->wrapper('chmod'));
        $messenger->registerRpc('chown',    $this->wrapper('chown'));
        $messenger->registerRpc('stat',     $this->wrapper('stat'));
        $messenger->registerRpc('readdir',  $this->wrapper('readdir'));
        $messenger->registerRpc('touch',    $this->wrapper('touch'));
        $messenger->registerRpc('open',     $this->wrapper('open'));
        $messenger->registerRpc('read',     $this->wrapper('read'));
        $messenger->registerRpc('write',    $this->wrapper('write'));
        $messenger->registerRpc('close',    $this->wrapper('close'));
        $messenger->registerRpc('rename',   $this->wrapper('rename'));
        $messenger->registerRpc('readlink', $this->wrapper('readlink'));
        $messenger->registerRpc('symlink',  $this->wrapper('symlink'));
    }

    protected function wrapper($function)
    {
        return function (Payload $payload, Messenger $messenger) use ($function) {
            return $this->$function($payload->getPayload());
        };
    }
}
