<?php

namespace React\Filesystem\Fallback;

use React\EventLoop\LoopInterface;
use React\Filesystem\Node\FileInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use function WyriHaximus\React\FallbackPromiseClosure;

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
        return resolve(file_get_contents($path, false, null, $offset, $maxlen ?? (int)stat($path)['size']));
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
        return resolve(file_put_contents($path, $contents, $flags));
    }

    public function unlink(): PromiseInterface
    {
        $path = $this->path . $this->name;
        return resolve(unlink($path));
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
