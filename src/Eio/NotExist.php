<?php

namespace React\Filesystem\Eio;

use React\EventLoop\LoopInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Node;
use React\Filesystem\PollInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

final class NotExist implements Node\NotExistInterface
{
    use StatTrait;

    private LoopInterface $loop;
    private PollInterface $poll;
    private AdapterInterface $filesystem;
    private string $path;
    private string $name;

    public function __construct(PollInterface $poll, AdapterInterface $filesystem, LoopInterface $loop, string $path, string $name)
    {
        $this->poll = $poll;
        $this->filesystem = $filesystem;
        $this->loop = $loop;
        $this->path = $path;
        $this->name = $name;
    }

    public function stat(): PromiseInterface
    {
        return $this->internalStat($this->path . $this->name);
    }

    public function createDirectory(): PromiseInterface
    {
        return $this->filesystem->detect($this->path)->then(function (Node\NodeInterface $node): PromiseInterface {
            if ($node instanceof Node\NotExistInterface) {
                return $node->createDirectory();
            }

            return resolve($node);
        })->then(function (Node\DirectoryInterface $directory): PromiseInterface {
            return new Promise(function (callable $resolve): void {
                $this->activate();
                \eio_mkdir($this->path . $this->name, 0777, \EIO_PRI_DEFAULT, function () use ($resolve): void {
                    $this->deactivate();
                    $resolve($this->filesystem->directory($this->path . $this->name));
                });
            });
        });
    }

    public function createFile(): PromiseInterface
    {
        $file = new File($this->poll, $this->path, $this->name);

        return $this->filesystem->detect($this->path)->then(function (Node\NodeInterface $node): PromiseInterface {
            if ($node instanceof Node\NotExistInterface) {
                return $node->createDirectory();
            }

            return resolve($node);
        })->then(function () use ($file): PromiseInterface {
            $this->activate();
            return new Promise(function (callable $resolve, callable $reject): void {
                \eio_open(
                    $this->path . DIRECTORY_SEPARATOR . $this->name,
                    \EIO_O_RDWR | \EIO_O_CREAT,
                    0777,
                    \EIO_PRI_DEFAULT,
                    function ($_, $fileDescriptor) use ($resolve, $reject): void {
                        try {
                            \eio_close($fileDescriptor, \EIO_PRI_DEFAULT, function () use ($resolve) {
                                $this->deactivate();
                                $resolve($this->filesystem->file($this->path . $this->name));
                            });
                        } catch (\Throwable $error) {
                            $this->deactivate();
                            $reject($error);
                        }
                    }
                );
            });
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

    protected function activate(): void
    {
        $this->poll->activate();
    }

    protected function deactivate(): void
    {
        $this->poll->deactivate();
    }
}
