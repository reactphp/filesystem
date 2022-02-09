<?php

namespace React\Filesystem\Uv;

use React\EventLoop\ExtUvLoop;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\NodeNotFound;
use React\Filesystem\Stat;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use RuntimeException;
use React\EventLoop\LoopInterface;
use React\Filesystem\Node;
use UV;

trait StatTrait
{
    protected function internalStat(string $path): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($path): void {
            $this->activate();
            uv_fs_stat($this->uvLoop(), $path, function ($stat) use ($path, $resolve, $reject): void {
                $this->deactivate();
                if (is_array($stat)) {
                    $resolve(new Stat($path, $stat));
                } else {
                    $resolve(null);
                }
            });
        });
    }
    
    abstract protected function uvLoop(); // phpcs:disabled
    abstract protected function activate(): void;
    abstract protected function deactivate(): void;
}
