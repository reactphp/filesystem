<?php

namespace React\Filesystem\Fallback;

use React\EventLoop\LoopInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Node;
use React\Promise\PromiseInterface;
use function React\Promise\all;
use function React\Promise\resolve;
use function WyriHaximus\React\FallbackPromiseClosure;

final class Directory implements Node\DirectoryInterface
{
    use StatTrait;

    private AdapterInterface $filesystem;
    private string $path;
    private string $name;

    public function __construct(AdapterInterface $filesystem, string $path, string $name)
    {
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
        $path = $this->path . $this->name;
        $promises = [];
        foreach (scandir($path) as $node) {
            if (in_array($node, ['.', '..'])) {
                continue;
            }

            $promises[] = $this->filesystem->detect($this->path . $this->name . DIRECTORY_SEPARATOR . $node);
        }

        return all($promises);
    }

    public function unlink(): PromiseInterface
    {
        $path = $this->path . $this->name;
        if (count(scandir($path)) > 0) {
            return resolve(false);
        }

        return resolve(rmdir($path));
    }

    public function path(): string
    {
        return $this->path;
    }

    public function name(): string
    {
        return $this->name;
    }
}
