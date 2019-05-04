<?php

namespace React\Filesystem\Uv;

use DateTime;
use Exception;
use InvalidArgumentException;
use React\EventLoop\ExtUvLoop;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\CallInvokerInterface;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\MappedTypeDetector;
use React\Filesystem\ModeTypeDetector;
use React\Filesystem\Node\NodeInterface;
use React\Filesystem\ObjectStream;
use React\Filesystem\ObjectStreamSink;
use React\Filesystem\TypeDetectorInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class Adapter implements AdapterInterface
{
    /**
     * @var ExtUvLoop
     */
    protected $loop;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var OpenFlagResolver
     */
    protected $openFlagResolver;

    /**
     * @var PermissionFlagResolver
     */
    protected $permissionFlagResolver;

    /**
     * @var TypeDetectorInterface[]
     */
    protected $typeDetectors;

    /**
     * @var array
     */
    protected $fileDescriptors = [];

    /**
     * @var array
     */
    protected $options = [
        'lsFlags' => 0,
        'symlinkFlags' => 0,
    ];

    /**
     * @var int
     */
    protected $workCounter = 0;
    
    /**
     * @var TimerInterface|null
     */
    protected $workTimer;

    /**
     * @var int
     */
    protected $workInterval;

    /**
     * @inheritDoc
     */
    public function __construct(ExtUvLoop $loop, array $options = [])
    {
        $this->loop = $loop;
        $this->options = \array_merge($this->options, $options);
        $this->workInterval = (int) (\PHP_INT_MAX / 1000) - 1;

        $this->openFlagResolver = new OpenFlagResolver();
        $this->permissionFlagResolver = new PermissionFlagResolver();
    }

    /**
     * @return bool
     */
    public static function isSupported()
    {
        return \extension_loaded('uv');
    }

    /** To be removed */
    public function getInvoker() {}
    public function setInvoker(CallInvokerInterface $invoker) {}

    /**
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilesystem()
    {
         return $this->filesystem;
    }

    /**
     * {@inheritDoc}
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;

        $this->typeDetectors = [
            MappedTypeDetector::createDefault($this->filesystem),
            new ModeTypeDetector($this->filesystem),
        ];
    }

    /**
     * Call the underlying filesystem.
     *
     * @internal
     * @param callable $function
     * @param array $args
     * @param callable $callable
     * @return PromiseInterface
     */
    public function callFilesystem($function, $args, $callable = null)
    {
        $deferred = new Deferred();

        if (!\is_callable($callable)) {
            $deferred->reject(new InvalidArgumentException('Invalid callable'));
            return $deferred->promise();
        }

        $this->register();
        $args[] = function (...$args) use ($deferred, $callable) {
            $this->unregister();

            try {
                $deferred->resolve(\call_user_func_array($callable, $args));
            } catch (\Throwable $e) {
                $deferred->reject($e);
            }
        };

        try {
            \call_user_func($function, $this->loop->getUvLoop(), ...$args);
        } catch (\Throwable $e) {
            $deferred->reject($e);
        }

        return $deferred->promise();
    }

    /**
     * @param string $path
     * @param int $mode
     * @return PromiseInterface
     */
    public function chmod($path, $mode)
    {
        return $this->callFilesystem('uv_fs_chmod', [
            $path,
            $mode,
        ], function ($result) {
            if ($result === false) {
                throw new Exception('Unable to set chmod on target');
            }
        });
    }

    /**
     * @param string $path
     * @param mixed  $mode
     * @return PromiseInterface
     */
    public function mkdir($path, $mode = self::CREATION_MODE)
    {
        return $this->callFilesystem('uv_fs_mkdir', [
            $path,
            $this->permissionFlagResolver->resolve($mode),
        ], function ($result) {
            if ($result === false) {
                throw new Exception('Unable to create directory at path');
            }
        });
    }

    /**
     * @param string $path
     * @return PromiseInterface
     */
    public function rmdir($path)
    {
        return $this->callFilesystem('uv_fs_rmdir', [
            $path,
        ], function ($result) {
            if ($result === false) {
                throw new Exception('Unable to delete directory');
            }
        });
    }

    /**
     * @param string $path
     * @return PromiseInterface
     */
    public function unlink($path)
    {
        return $this->callFilesystem('uv_fs_unlink', [
            $path,
        ], function ($result) {
            if ($result === false) {
                throw new Exception('Unable to delete the target');
            }
        });
    }

    /**
     * @param string $path
     * @param int $uid
     * @param int $gid
     * @return PromiseInterface
     */
    public function chown($path, $uid, $gid)
    {
        return $this->callFilesystem('uv_fs_chown', [
            $path,
            $uid,
            $gid,
        ], function ($result) {
            if ($result === false) {
                throw new Exception('Unable to chown the target');
            }
        });
    }

    /**
     * @param string $filename
     * @return PromiseInterface
     */
    public function stat($filename)
    {
        return $this->callFilesystem('uv_fs_lstat', [
            $filename,
        ], function ($bool, $stat = null) {
            if ($bool !== true) {
                throw new Exception('Unable to stat the target');
            }

            $stat['blksize'] = $stat['blksize'] ?? -1;
            $stat['blocks'] = $stat['blocks'] ?? -1;
            $stat['atime'] = new DateTime('@' . $stat['atime']);
            $stat['mtime'] = new DateTime('@' . $stat['mtime']);
            $stat['ctime'] = new DateTime('@' . $stat['ctime']);

            return $stat;
        });
    }

    /**
     * @param string $path
     * @return PromiseInterface
     */
    public function ls($path)
    {
        return ObjectStreamSink::promise($this->lsStream($path));
    }

    /**
     * @param string $path
     * @return ObjectStream
     */
    public function lsStream($path)
    {
        $stream = new ObjectStream();

        $this->callFilesystem('uv_fs_scandir', [
            $path,
            $this->options['lsFlags'],
        ], function ($bool, $result = null) use ($path, $stream) {
            if ($bool !== true) {
                throw new Exception('Unable to list the directory');
            }
    
            $this->processLsContents($path, $result, $stream);
        });

        return $stream;
    }
    
    protected function processLsContents($basePath, $result, ObjectStream $stream)
    {
        $this->register();
        $promises = [];

        foreach ($result as $entry) {
            $path = $basePath . \DIRECTORY_SEPARATOR . $entry;

            $promises[] = $this->stat($path)->then(function ($stat) use ($path, $stream) {
                $node = [
                    'path' => $path,
                    'mode' => $stat['mode'],
                    'type' => null,
                ];

                return \React\Filesystem\detectType($this->typeDetectors, $node)->then(function (NodeInterface $node) use ($stream) {
                    $stream->write($node);
                });
            });
        }

        \React\Promise\all($promises)->then(function () use ($stream) {
            $this->unregister();
            $stream->close();
        });
    }

    /**
     * @param string $path
     * @param mixed  $mode
     * @return PromiseInterface
     */
    public function touch($path, $mode = self::CREATION_MODE)
    {
        return $this->appendContents($path, '')->then(function () use ($path) {
            return $this->callFilesystem('uv_fs_utime', [
                $path,
                \time(),
                \time(),
            ], function ($result) {
                if ($result === false) {
                    throw new Exception('Unable to touch target');
                }
            });
        });
    }

    /**
     * @param string $path
     * @param string $flags
     * @param mixed  $mode
     * @return PromiseInterface
     */
    public function open($path, $flags, $mode = self::CREATION_MODE)
    {
        return $this->callFilesystem('uv_fs_open', [
            $path,
            $this->openFlagResolver->resolve($flags),
            $this->permissionFlagResolver->resolve($mode),
        ], function ($fd) {
            if ($fd === false) {
                throw new Exception('Unable to open file, make sure the file exists and is readable');
            }

            $this->fileDescriptors[(int) $fd] = $fd;
            return (int) $fd;
        });
    }

    /**
     * @param string $fileDescriptor
     * @param int $length
     * @param int $offset
     * @return PromiseInterface
     */
    public function read($fileDescriptor, $length, $offset)
    {
        if (empty($this->fileDescriptors[$fileDescriptor])) {
            return \React\Promise\reject(new Exception('Unknown file descriptor'));
        }

        $fd = $this->fileDescriptors[$fileDescriptor];
    
        return $this->callFilesystem('uv_fs_read', [
            $fd,
            $offset,
            $length,
        ], function ($fd, $nread, $buffer) {
            return $buffer;
        });
    }

    /**
     * @param string $fileDescriptor
     * @param string $data
     * @param int $length Unused.
     * @param int $offset
     * @return PromiseInterface
     */
    public function write($fileDescriptor, $data, $length, $offset)
    {
        if (empty($this->fileDescriptors[$fileDescriptor])) {
            return \React\Promise\reject(new Exception('Unknown file descriptor'));
        }

        $fd = $this->fileDescriptors[$fileDescriptor];

        return $this->callFilesystem('uv_fs_write', [
            $fd,
            $data,
            $offset,
        ], function ($fd, $result) {
            return $result;
        });
    }

    /**
     * @param string $fileDescriptor
     * @return PromiseInterface
     */
    public function close($fileDescriptor)
    {
        if (empty($this->fileDescriptors[$fileDescriptor])) {
            return \React\Promise\reject(new Exception('Unknown file descriptor'));
        }

        $fd = $this->fileDescriptors[$fileDescriptor];
        unset($this->fileDescriptors[$fileDescriptor]);

        return $this->callFilesystem('uv_fs_close', [
            $fd,
        ], function () {
            // NO-OP
        });
    }

    /**
     * Reads the entire file.
     *
     * This is an optimization for adapters which can optimize
     * the open -> (seek ->) read -> close sequence into one call.
     *
     * @param string $path
     * @param int $offset
     * @param int|null $length
     * @return PromiseInterface
     */
    public function getContents($path, $offset = 0, $length = null)
    {
        if ($length === null) {
            return $this->stat($path)->then(function ($stat) use ($path, $offset) {
                return $this->getContents($path, $offset, $stat['size']);
            });
        }

        return $this->open($path, 'r')->then(function ($fd) use ($offset, $length) {
            return $this->read($fd, $length, $offset)->always(function () use ($fd) {
                return $this->close($fd);
            });
        });
    }

    /**
     * Writes the given content to the specified file.
     * If the file exists, the file is truncated.
     * If the file does not exist, the file will be created.
     *
     * This is an optimization for adapters which can optimize
     * the open -> write -> close sequence into one call.
     *
     * @param string $path
     * @param string $content
     * @return PromiseInterface
     * @see AdapterInterface::appendContents()
     */
    public function putContents($path, $content)
    {
        return $this->open($path, 'ctw')->then(function ($fd) use ($content) {
            return $this->write($fd, $content, strlen($content), 0)->always(function () use ($fd) {
                return $this->close($fd);
            });
        });
    }

    /**
     * Appends the given content to the specified file.
     * If the file does not exist, the file will be created.
     *
     * This is an optimization for adapters which can optimize
     * the open -> write -> close sequence into one call.
     *
     * @param string $path
     * @param string $content
     * @return PromiseInterface
     * @see AdapterInterface::putContents()
     */
    public function appendContents($path, $content)
    {
        return $this->open($path, 'ca')->then(function ($fd) use ($content) {
            return $this->write($fd, $content, strlen($content), 0)->always(function () use ($fd) {
                return $this->close($fd);
            });
        });
    }

    /**
     * @param string $fromPath
     * @param string $toPath
     * @return PromiseInterface
     */
    public function rename($fromPath, $toPath)
    {
        return $this->callFilesystem('uv_fs_rename', [
            $fromPath,
            $toPath,
        ], function ($result) {
            if ($result === false) {
                throw new Exception('Unable to rename target');
            }
        });
    }

    /**
     * @param string $path
     * @return PromiseInterface
     */
    public function readlink($path)
    {
        return $this->callFilesystem('uv_fs_readlink', [
            $path,
        ], function ($bool, $result) {
            if ($bool === false) {
                throw new Exception('Unable to read link of target');
            }

            return $result;
        });
    }

    /**
     * @param string $fromPath
     * @param string $toPath
     * @return PromiseInterface
     */
    public function symlink($fromPath, $toPath)
    {
        return $this->callFilesystem('uv_fs_symlink', [
            $fromPath,
            $toPath,
            $this->options['symlinkFlags'],
        ], function ($result) {
            if ($result === false) {
                throw new Exception('Unable to create a symlink for the target');
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function detectType($path)
    {
        return \React\Filesystem\detectType($this->typeDetectors, [
            'path' => $path,
        ]);
    }

    /**
     * Registers work and possibly the timer.
     */
    protected function register()
    {
        if ($this->workCounter++ <= 0) {
            $this->workTimer = $this->loop->addTimer($this->workInterval, function () {});
        }
    }

    /**
     * Unregisters work and possibly the timer.
     */
    protected function unregister()
    {
        if (--$this->workCounter <= 0) {
            $this->loop->cancelTimer($this->workTimer);
        }
    }
}
