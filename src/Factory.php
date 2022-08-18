<?php

namespace React\Filesystem;

use React\EventLoop\ExtUvLoop;
use React\EventLoop\Loop;
use React\Filesystem\Uv;
use React\Filesystem\Eio;

final class Factory
{
    public static function create(): AdapterInterface
    {
        if (\function_exists('eio_get_event_stream')) {
            return new Eio\Adapter();
        }

        if (\function_exists('uv_loop_new') && Loop::get() instanceof ExtUvLoop) {
            return new Uv\Adapter();
        }

        return new Fallback\Adapter();
    }
}
