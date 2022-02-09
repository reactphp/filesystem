<?php

namespace React\Filesystem\Uv;

use React\EventLoop\ExtUvLoop;
use React\EventLoop\Loop;
use React\Filesystem\AdapterInterface;
use React\Filesystem\ModeTypeDetector;
use React\Filesystem\PollInterface;
use React\Filesystem\Stat;
use React\Promise\PromiseInterface;
use React\Filesystem\Node;

final class Adapter implements AdapterInterface
{
    use StatTrait;

    private ExtUvLoop $loop;
    private $uvLoop;
    private PollInterface $poll;

    public function __construct()
    {
        $loop = Loop::get();
        if (!($loop instanceof ExtUvLoop)) {
            throw new \InvalidArgumentException('Event loop is expected to be ext-uv based, which it is not');
        }
        $this->loop = $loop;
        $this->poll = new Poll($this->loop);
        $this->uvLoop = $loop->getUvLoop();
    }

    public function detect(string $path): PromiseInterface
    {
        return $this->internalStat($path)->then(function (?Stat $stat) use ($path) {
            if ($stat === null) {
                return new NotExist($this->poll, $this, $this->loop, dirname($path) . DIRECTORY_SEPARATOR, basename($path));
            }

            switch (ModeTypeDetector::detect($stat->mode())) {
                case Node\DirectoryInterface::class:
                    return $this->directory($stat->path());
                    break;
                case Node\FileInterface::class:
                    return $this->file($stat->path());
                    break;
                default:
                    return new Node\Unknown($stat->path(), $stat->path());
                    break;
            }
        });
    }

    public function directory(string $path): Node\DirectoryInterface
    {
        return new Directory($this->poll, $this, $this->loop, dirname($path) . DIRECTORY_SEPARATOR, basename($path));
    }

    public function file(string $path): Node\FileInterface
    {
        return new File($this->poll, $this->loop, dirname($path) . DIRECTORY_SEPARATOR, basename($path));
    }


    protected function uvLoop()
    {
        return $this->uvLoop;
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
