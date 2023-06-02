<?php

namespace React\Filesystem\Eio;

use React\Filesystem\AdapterInterface;
use React\Filesystem\Node;
use React\Filesystem\PollInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use function React\Promise\all;

final class Directory implements Node\DirectoryInterface
{
    use StatTrait;

    private PollInterface $poll;
    private AdapterInterface $filesystem;
    private string $path;
    private string $name;

    public function __construct(PollInterface $poll, AdapterInterface $filesystem, string $path, string $name)
    {
        $this->poll = $poll;
        $this->filesystem = $filesystem;
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
        return new Promise(function (callable $resolve, callable $reject): void {
            \eio_readdir($this->path . $this->name . DIRECTORY_SEPARATOR, \EIO_READDIR_STAT_ORDER | \EIO_READDIR_DIRS_FIRST, \EIO_PRI_DEFAULT, function ($_, $contents, $resource) use ($resolve, $reject): void {
                $this->deactivate();
                $list = [];
                if ($contents === -1) {
                    $reject(new \RuntimeException('Error reading from directory "' . $this->path . $this->name . DIRECTORY_SEPARATOR . '": ' . \eio_get_last_error($resource)));
                    return;
                }

                foreach ($contents['dents'] as $node) {
                    $fullPath = $this->path . $this->name . DIRECTORY_SEPARATOR . $node['name'];
                    switch ($node['type'] ?? null) {
                        case EIO_DT_DIR:
                            $list[] = $this->filesystem->directory($fullPath);
                            break;
                        case EIO_DT_REG :
                            $list[] = $this->filesystem->file($fullPath);
                            break;
                        default:
                            $list[] = $this->filesystem->detect($this->path . $this->name . DIRECTORY_SEPARATOR . $node['name']);
                            break;
                    }
                }

                $resolve(all($list));
            });
        });
    }

    public function unlink(): PromiseInterface
    {
        $this->activate();
        return new Promise(function (callable $resolve): void {
            \eio_readdir($this->path . $this->name . DIRECTORY_SEPARATOR, \EIO_READDIR_STAT_ORDER | \EIO_READDIR_DIRS_FIRST, \EIO_PRI_DEFAULT, function ($_, $contents) use ($resolve): void {
                $this->deactivate();
                if (count($contents['dents']) > 0) {
                    $resolve(false);

                    return;
                }

                $this->activate();
                \eio_rmdir($this->path . $this->name, function () use ($resolve): void {
                    $this->deactivate();
                    $resolve(true);
                });
            });
        });
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
