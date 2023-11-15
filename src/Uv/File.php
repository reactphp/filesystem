<?php

namespace React\Filesystem\Uv;

use React\EventLoop\ExtUvLoop;
use React\EventLoop\Loop;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\PollInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use UV;

final class File implements FileInterface
{
//    private const READ_CHUNK_FIZE = 65536;
    private const READ_CHUNK_FIZE = 1;

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
        return $this->openFile(
            $this->path . DIRECTORY_SEPARATOR . $this->name,
            UV::O_RDONLY,
            0,
        )->then(
            function ($fileDescriptor) use ($offset, $maxlen): PromiseInterface {
                $buffer = '';
                $bufferLength = 0;
                $read = function (bool $finalAttempt, int $offset) use ($fileDescriptor, $maxlen, &$read, &$buffer, &$bufferLength): PromiseInterface {
                    return new Promise (function (callable $resolve) use ($fileDescriptor, $offset, $maxlen, $finalAttempt, &$read, &$buffer, &$bufferLength): void {
                        \uv_fs_read($this->uvLoop, $fileDescriptor, $offset, $maxlen ?? self::READ_CHUNK_FIZE, function ($fileDescriptor, string $contents) use ($resolve, $maxlen, $finalAttempt, &$read, &$buffer, &$bufferLength): void {
                            $contentLength = strlen($contents);
                            $buffer .= $contents;
                            $bufferLength += $contentLength;

                            if (
                                ($maxlen === null && $finalAttempt) ||
                                ($maxlen !== null && $bufferLength >= $maxlen)
                            ) {
                                if ($maxlen !== null && $bufferLength > $maxlen) {
                                    $buffer = substr($buffer, 0, $maxlen);
                                }

                                $resolve($this->closeOpenFile($fileDescriptor)->then(function () use ($buffer): string {
                                    $this->deactivate();

                                    return $buffer;
                                }));
                            } else if ($maxlen === null && !$finalAttempt && $contentLength === 0) {
                                $resolve($read(true, $bufferLength));
                            } else {
                                $resolve($read(false, $bufferLength));
                            }
                        });
                    });
                };

                return $read(false, $offset);
            }
        );
    }

    public function putContents(string $contents, int $flags = 0)
    {
        $this->activate();
        return $this->openFile(
            $this->path . DIRECTORY_SEPARATOR . $this->name,
            (($flags & \FILE_APPEND) == \FILE_APPEND) ? UV::O_RDWR | UV::O_CREAT | UV::O_APPEND : UV::O_RDWR | UV::O_CREAT,
            0644,
        )->then(
            function ($fileDescriptor) use ($contents): PromiseInterface {
                return new Promise (function (callable $resolve) use ($contents, $fileDescriptor): void {
                    uv_fs_write($this->uvLoop, $fileDescriptor, $contents, 0, function ($fileDescriptor, int $bytesWritten) use ($resolve): void {
                        $resolve($this->closeOpenFile($fileDescriptor)->then(function () use ($bytesWritten): int {
                            $this->deactivate();
                            return $bytesWritten;
                        }));
                    });
                }
            );
        });
    }

    private function openFile(string $path, int $flags, int $mode): PromiseInterface
    {
        $this->activate();
        return new Promise(function (callable $resolve) use ($path, $flags, $mode): void {
            uv_fs_open(
                $this->uvLoop,
                $this->path . DIRECTORY_SEPARATOR . $this->name,
                $flags,
                $mode,
                function ($fileDescriptor) use ($resolve): void {
                    $this->deactivate();
                    $resolve($fileDescriptor);
                }
            );
        });
    }

    private function closeOpenFile($fileDescriptor): PromiseInterface
    {
        $this->activate();
        return new Promise(function (callable $resolve) use ($fileDescriptor) {
            try {
                uv_fs_close($this->uvLoop, $fileDescriptor, function () use ($resolve) {
                    $this->deactivate();
                    $resolve();
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
