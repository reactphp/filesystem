<?php

namespace React\Filesystem\Uv;

use React\EventLoop\ExtUvLoop;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Node;
use React\Filesystem\PollInterface;
use React\Promise\CancellablePromiseInterface;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use function React\Promise\all;
use function React\Promise\resolve;

final class NotExist implements Node\NotExistInterface
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

    public function createDirectory(): PromiseInterface
    {
        $this->activate();
        return $this->filesystem->detect($this->path)->then(function (Node\NodeInterface $node): PromiseInterface {
            if ($node instanceof Node\NotExistInterface) {
                return $node->createDirectory();
            }

            return resolve($node);
        })->then(function (Node\DirectoryInterface $directory): PromiseInterface {
            return new Promise(function (callable $resolve): void {
                uv_fs_mkdir($this->uvLoop, $this->path . $this->name, 0777, function () use ($resolve): void {
                    $resolve(new Directory($this->poll, $this->filesystem, $this->loop, $this->path, $this->name));
                    $this->deactivate();
                });
            });
        });
    }

    public function createFile(): PromiseInterface
    {
        $file = new File($this->poll, $this->loop, $this->path, $this->name);

        return $this->filesystem->detect($this->path . DIRECTORY_SEPARATOR)->then(function (Node\NodeInterface $node): PromiseInterface {
            if ($node instanceof Node\NotExistInterface) {
                return $node->createDirectory();
            }

            return resolve($node);
        })->then(function () use ($file): PromiseInterface {
            return $file->putContents('');
        })->then(function () use ($file): Node\FileInterface {
            return $file;
        });
    }

    public function unlink(): PromiseInterface
    {
        // Essentially a No-OP since it doesn't exist anyway
        return resolve(true);
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
