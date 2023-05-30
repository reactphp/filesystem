<?php

namespace React\Filesystem\Eio;

use React\Filesystem\Stat;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

trait StatTrait
{
    protected function internalStat(string $path): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($path): void {
            $this->activate();
            eio_lstat($path, EIO_PRI_DEFAULT, function ($_, $stat) use ($path, $resolve, $reject): void {
                try {
                    $this->deactivate();
                    if (is_array($stat)) {
                        $resolve(new Stat($path, $stat));
                    } else {
                        $resolve(null);
                    }
                } catch (\Throwable $error) {
                    $reject($error);
                }
            });
        });
    }
    
    abstract protected function activate(): void;
    abstract protected function deactivate(): void;
}
