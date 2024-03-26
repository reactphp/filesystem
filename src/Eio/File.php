<?php

namespace React\Filesystem\Eio;

use React\Filesystem\Node\FileInterface;
use React\Filesystem\PollInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

final class File implements FileInterface
{
    use StatTrait;

    private PollInterface $poll;
    private string $path;
    private string $name;

    public function __construct(PollInterface $poll, string $path, string $name)
    {
        $this->poll = $poll;
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
        return $this->openFile(
            $this->path . DIRECTORY_SEPARATOR . $this->name,
            \EIO_O_RDONLY,
            0,
        )->then(
            function ($fileDescriptor) use ($offset, $maxlen): PromiseInterface {
                if ($maxlen === null) {
                    $sizePromise = $this->statFileDescriptor($fileDescriptor)->then(static function ($stat): int {
                        return (int)$stat['size'];
                    });
                } else {
                    $sizePromise = resolve($maxlen);
                }
                return $sizePromise->then(function ($length) use ($fileDescriptor, $offset): PromiseInterface {
                    return new Promise (function (callable $resolve) use ($fileDescriptor, $offset, $length): void {
                        \eio_read($fileDescriptor, $length, $offset, \EIO_PRI_DEFAULT, function ($fileDescriptor, string $buffer) use ($resolve): void {
                            $resolve($this->closeOpenFile($fileDescriptor)->then(function () use ($buffer): string {
                                return $buffer;
                            }));
                        }, $fileDescriptor);
                    });
                });
            }
        );
    }

    public function putContents(string $contents, int $flags = 0)
    {
        $this->activate();
        return $this->openFile(
                $this->path . DIRECTORY_SEPARATOR . $this->name,
                (($flags & \FILE_APPEND) == \FILE_APPEND) ? \EIO_O_RDWR | \EIO_O_APPEND : \EIO_O_RDWR | \EIO_O_CREAT,
                0644
        )->then(
            function ($fileDescriptor) use ($contents, $flags): PromiseInterface {
                return new Promise (function (callable $resolve) use ($contents, $fileDescriptor): void {
                    \eio_write($fileDescriptor, $contents, strlen($contents), 0, \EIO_PRI_DEFAULT, function ($fileDescriptor, int $bytesWritten) use ($resolve): void {
                        $resolve($this->closeOpenFile($fileDescriptor)->then(function () use ($bytesWritten): int {
                            return $bytesWritten;
                        }));
                    }, $fileDescriptor);
                });
            }
        );
    }

    private function statFileDescriptor($fileDescriptor): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($fileDescriptor) {
            \eio_fstat($fileDescriptor, \EIO_PRI_DEFAULT, function ($_, $stat) use ($resolve): void {
                $resolve($stat);
            }, $fileDescriptor);
        });
    }

    private function openFile(string $path, int $flags, int $mode): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($path, $flags, $mode): void {
            \eio_open(
                $path,
                $flags,
                $mode,
                \EIO_PRI_DEFAULT,
                function ($_, $fileDescriptor) use ($resolve): void {
                    $resolve($fileDescriptor);
                }
            );
        });
    }

    private function closeOpenFile($fileDescriptor): PromiseInterface
    {
        return new Promise(function (callable $resolve) use ($fileDescriptor) {
            try {
                \eio_close($fileDescriptor, \EIO_PRI_DEFAULT, function () use ($resolve): void {
                    $this->deactivate();
                    $resolve(true);
                });
            } catch (\Throwable $error) {
                $this->deactivate();
                throw $error;
            }
        });
    }

    public function unlink(): PromiseInterface
    {
        $this->activate();
        return new Promise(function (callable $resolve): void {
            \eio_unlink($this->path . DIRECTORY_SEPARATOR . $this->name, \EIO_PRI_DEFAULT, function () use ($resolve): void {
                $this->deactivate();
                $resolve(true);
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
