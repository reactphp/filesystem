<?php

namespace React\Filesystem\Uv;

use React\EventLoop\ExtUvLoop;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\PollInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use UV;

final class File implements FileInterface
{
    use StatTrait;

    private ExtUvLoop $loop;
    private $uvLoop;
    private PollInterface $poll;
    private string $path;
    private string $name;

    public function __construct(PollInterface $poll, ExtUvLoop $loop, string $path, string $name)
    {
        $this->poll = $poll;
        $this->loop = $loop;
        $this->uvLoop = $loop->getUvLoop();
        $this->path = $path;
        $this->name = $name;
    }

    public function stat(): PromiseInterface
    {
        return $this->internalStat($this->path . $this->name);
    }

    public function getContents(int $offset = 0 , ?int $maxlen = null): PromiseInterface
    {
        $this->activate();
        return new Promise(function (callable $resolve) use ($offset, $maxlen): void {
            uv_fs_open($this->uvLoop, $this->path . DIRECTORY_SEPARATOR . $this->name, UV::O_RDONLY, 0, function ($fileDescriptor) use ($resolve, $offset, $maxlen): void {
                uv_fs_fstat($this->uvLoop, $fileDescriptor, function ($fileDescriptor, array $stat) use ($resolve, $offset, $maxlen): void {
                    uv_fs_read($this->uvLoop, $fileDescriptor, $offset, $maxlen ?? (int)$stat['size'], function ($fileDescriptor, string $buffer) use ($resolve): void {
                        $resolve($buffer);
                        uv_fs_close($this->uvLoop, $fileDescriptor, function () {
                            $this->deactivate();
                        });
                    });
                });
            });
        });
    }

    public function putContents(string $contents, int $flags = 0)
    {
        $this->activate();
        return new Promise(function (callable $resolve) use ($contents, $flags): void {
            uv_fs_open(
                $this->uvLoop,
                $this->path . DIRECTORY_SEPARATOR . $this->name,
                (($flags & \FILE_APPEND) == \FILE_APPEND) ? UV::O_RDWR | UV::O_CREAT | UV::O_APPEND : UV::O_RDWR | UV::O_CREAT,
                0644,
                function ($fileDescriptor) use ($resolve, $contents, $flags): void {
                    uv_fs_write($this->uvLoop, $fileDescriptor, $contents, 0, function ($fileDescriptor, int $bytesWritten) use ($resolve): void {
                        $resolve($bytesWritten);
                        uv_fs_close($this->uvLoop, $fileDescriptor, function () {
                            $this->deactivate();
                        });
                    });
                }
            );
        });
    }

    public function unlink(): PromiseInterface
    {
        $this->activate();
        return new Promise(function (callable $resolve): void {
            uv_fs_unlink($this->uvLoop, $this->path . DIRECTORY_SEPARATOR . $this->name, function () use ($resolve): void {
                $resolve(true);
                $this->deactivate();
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
