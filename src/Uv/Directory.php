<?php

namespace React\Filesystem\Uv;

use React\EventLoop\ExtUvLoop;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Node;
use React\Filesystem\PollInterface;
use React\Promise\CancellablePromiseInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use function React\Promise\all;
use function React\Promise\resolve;

final class Directory implements Node\DirectoryInterface
{
    use StatTrait;

    private ExtUvLoop $loop;
    private $uvLoop;
    private PollInterface $poll;
    private AdapterInterface $filesystem;
    private string $path;
    private string $name;

    public function __construct(PollInterface $poll, AdapterInterface $filesystem, ExtUvLoop $loop, string $path, string $name)
    {
        $this->poll = $poll;
        $this->filesystem = $filesystem;
        $this->loop = $loop;
        $this->uvLoop = $loop->getUvLoop();
        $this->path = $path;
        $this->name = $name;
    }

    public function stat(): PromiseInterface
    {
        return $this->internalStat($this->path . $this->name);
    }

    public function ls(): PromiseInterface
    {
        $this->activate();
        $deferred = new Deferred();
        uv_fs_scandir($this->uvLoop, $this->path . $this->name, function (array $contents) use ($deferred): void {
            $promises = [];
            foreach ($contents as $node) {
                $promises[] = $this->filesystem->detect($this->path . $this->name . DIRECTORY_SEPARATOR . $node)->then(null, function (\Throwable $throwable) use ($deferred, &$promises) {
                    $deferred->reject($throwable);
                    foreach ($promises as $promise) {
                        if ($promise instanceof CancellablePromiseInterface) {
                            $promise->cancel();
                        }
                    }
                });
            }

            $deferred->resolve(all($promises));
            $this->deactivate();
        });


        return $deferred->promise();
    }

    public function unlink(): PromiseInterface
    {
        $this->activate();
        $deferred = new Deferred();
        uv_fs_scandir($this->uvLoop, $this->path . $this->name, function (array $contents) use ($deferred): void {
            $this->deactivate();
            if (count($contents) > 0) {
                $deferred->resolve(false);

                return;
            }

            $this->activate();
            uv_fs_rmdir($this->uvLoop, $this->path . $this->name, function () use ($deferred): void {
                $this->deactivate();
                $deferred->resolve(true);
            });
        });


        return $deferred->promise();
    }

    public function path(): string
    {
        return $this->path;
    }

    public function name(): string
    {
        return $this->name;
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
