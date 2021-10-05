<?php

namespace React\Filesystem\ChildProcess;

use React\EventLoop\Loop;
use React\Filesystem\Node\FileInterface;
use React\Promise\PromiseInterface;
use function WyriHaximus\React\childProcessPromiseClosure;

/**
 * @internal
 */
final class File implements FileInterface
{
    use StatTrait;

    private string $path;
    private string $name;

    public function __construct(string $path, string $name)
    {
        $this->path = $path;
        $this->name = $name;
    }

    public function stat(): PromiseInterface
    {
        return $this->internalStat($this->path . $this->name);
    }

    public function getContents(int $offset = 0 , ?int $maxlen = null): PromiseInterface
    {
        $path = $this->path . $this->name;
        return childProcessPromiseClosure(Loop::get(), function () use ($path, $offset, $maxlen): array {
            return ['contents' => file_get_contents($path, false, null, $offset, $maxlen ?? (int)stat($path)['size'])];
        })->then(static fn (array $data): string => $data['contents']);
    }

    public function putContents(string $contents, int $flags = 0): PromiseInterface
    {
        // Making sure we only pass in one flag for security reasons
        if (($flags & \FILE_APPEND) == \FILE_APPEND) {
            $flags = \FILE_APPEND;
        } else {
            $flags = 0;
        }

        $path = $this->path . $this->name;
        return childProcessPromiseClosure(Loop::get(), function () use ($path, $contents, $flags): array {
            return ['size_written' => file_put_contents($path, $contents, $flags)];
        })->then(static fn (array $data) => (int)$data['size_written']);
    }

    public function unlink(): PromiseInterface
    {
        $path = $this->path . $this->name;
        return childProcessPromiseClosure(Loop::get(), function () use ($path): array {
            return ['unlinked' => unlink($path)];
        })->then(static fn (array $data) => (bool)$data['unlinked']);
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
