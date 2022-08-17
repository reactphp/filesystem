<?php

namespace React\Tests\Filesystem;

use React\EventLoop;
use React\Filesystem\Fallback;
use React\Filesystem\Uv;
use PHPUnit\Framework\TestCase;
use React\EventLoop\ExtUvLoop;
use React\EventLoop\LoopInterface;
use React\Filesystem\Factory;
use React\Filesystem\AdapterInterface;
use React\Promise\PromiseInterface;
use function Clue\React\Block\await;

abstract class AbstractFilesystemTestCase extends TestCase
{
    /**
     * @return iterable<array<AdapterInterface, LoopInterface>>
     */
    final public function provideFilesystems(): iterable
    {
        $loop = EventLoop\Loop::get();

        yield 'fallback' => [new Fallback\Adapter()];

        if (\function_exists('uv_loop_new') && $loop instanceof ExtUvLoop) {
            yield 'uv' => [new Uv\Adapter()];
        }

        yield 'factory' => [Factory::create()];
    }

    public function await(PromiseInterface $promise)
    {
        return await($promise, EventLoop\Loop::get(), 30);
    }
}
