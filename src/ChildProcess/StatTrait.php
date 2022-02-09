<?php

namespace React\Filesystem\ChildProcess;

use React\EventLoop\Loop;
use React\Filesystem\Stat;
use React\Promise\PromiseInterface;
use function WyriHaximus\React\childProcessPromiseClosure;

/**
 * @internal
 */
trait StatTrait
{
    protected function internalStat(string $path): PromiseInterface
    {
        return childProcessPromiseClosure(Loop::get(), function () use ($path): array {
            if (!file_exists($path)) {
                return [];
            }

            return stat($path);
        })->then(function (array $stat) use ($path): ?Stat {
            if (count($stat) > 0) {
                return new Stat($path, $stat);
            }

            return null;
        });
    }
}
