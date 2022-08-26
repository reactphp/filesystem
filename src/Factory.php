<?php

namespace React\Filesystem;

use React\EventLoop\ExtUvLoop;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Filesystem\Uv;
use React\Filesystem\Eio;
use React\Filesystem\ChildProcess;

final class Factory
{
    public static function create(): AdapterInterface
    {
        if (\function_exists('uv_loop_new') && Loop::get() instanceof ExtUvLoop) {
            return new Uv\Adapter();
        }

        return new Fallback\Adapter();
    }
}
