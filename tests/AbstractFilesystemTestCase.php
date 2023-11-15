<?php

namespace React\Tests\Filesystem;

use PHPUnit\Framework\TestCase;
use React\EventLoop;
use React\EventLoop\ExtUvLoop;
use React\EventLoop\LoopInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Eio;
use React\Filesystem\Factory;
use React\Filesystem\Fallback;
use React\Filesystem\Uv;

abstract class AbstractFilesystemTestCase extends TestCase
{
    /**
     * @return iterable<array<AdapterInterface, LoopInterface>>
     */
    final public function provideFilesystems(): iterable
    {
        $loop = EventLoop\Loop::get();

        yield 'fallback' => [new Fallback\Adapter()];

        if (\function_exists('eio_get_event_stream')) {
            yield 'eio' => [new Eio\Adapter()];
        }

        if (\function_exists('uv_loop_new') && $loop instanceof ExtUvLoop) {
            yield 'uv' => [new Uv\Adapter()];
        }

//        yield 'factory' => [Factory::create()];
    }
}
