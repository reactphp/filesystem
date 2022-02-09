<?php

namespace React\Filesystem\Fallback;

use React\EventLoop\ExtUvLoop;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Stat;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use RuntimeException;
use React\EventLoop\LoopInterface;
use React\Filesystem\Node;
use UV;
use function React\Promise\resolve;
use function WyriHaximus\React\FallbackPromiseClosure;

trait StatTrait
{
    protected function internalStat(string $path): PromiseInterface
    {
        if (!file_exists($path)) {
            return resolve(null);
        }

        return resolve(new Stat($path, stat($path)));
    }
}
