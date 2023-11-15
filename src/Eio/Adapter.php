<?php

namespace React\Filesystem\Eio;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\ModeTypeDetector;
use React\Filesystem\Node;
use React\Filesystem\PollInterface;
use React\Filesystem\Stat;
use React\Promise\PromiseInterface;

final class Adapter implements AdapterInterface
{
    use StatTrait;

    private LoopInterface $loop;
    private PollInterface $poll;

    public function __construct()
    {
        $this->loop = Loop::get();
        $this->poll = new Poll();
    }

    public function detect(string $path): PromiseInterface
    {
        return $this->internalStat($path)->then(function (?Stat $stat) use ($path) {
            if ($stat === null) {
                return new NotExist($this->poll, $this, $this->loop, dirname($path) . DIRECTORY_SEPARATOR, basename($path));
            }

            switch (ModeTypeDetector::detect($stat->mode())) {
                case Node\FileInterface::class:
                    return $this->file($stat->path());
                    break;
                case Node\DirectoryInterface::class:
                    return $this->directory($stat->path());
                    break;
                default:
                    return new Node\Unknown($stat->path(), $stat->path());
                    break;
            }
        });
    }

    public function directory(string $path): Node\DirectoryInterface
    {
        return new Directory($this->poll, $this, dirname($path) . DIRECTORY_SEPARATOR, basename($path));
    }

    public function file(string $path): Node\FileInterface
    {
        return new File($this->poll, dirname($path) . DIRECTORY_SEPARATOR, basename($path));
    }

    protected function activate(): void
    {
        $this->poll->activate();
    }

    protected function deactivate(): void
    {
        $this->poll->deactivate();
    }
}
