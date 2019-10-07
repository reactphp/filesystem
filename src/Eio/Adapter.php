<?php

namespace React\Filesystem\Eio;

use DateTime;
use React\EventLoop\LoopInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\ModeTypeDetector;
use React\Filesystem\Node\NodeInterface;
use React\Filesystem\ObjectStream;
use React\Filesystem\ObjectStreamSink;
use React\Filesystem\OpenFileLimiter;
use React\Filesystem\PermissionFlagResolver;
use React\Filesystem\TypeDetectorInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class Adapter implements AdapterInterface
{
    /**
     * @var bool
     */
    protected $active = false;

    /**
     * @var bool
     */
    protected $loopRunning = false;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var OpenFlagResolver
     */
    protected $openFlagResolver;

    /**
     * @var PermissionFlagResolver
     */
    protected $permissionFlagResolver;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var TypeDetectorInterface[]
     */
    protected $typeDetectors;

    /**
     * @var OpenFileLimiter
     */
    protected $openFileLimiter;

    /**
     * @var array
     */
    protected $options = [
        'lsFlags' => EIO_READDIR_STAT_ORDER,
    ];

    /**
     * @param LoopInterface $loop
     * @param array $options
     */
    public function __construct(LoopInterface $loop, array $options = [])
    {
        eio_init();
        $this->loop = $loop;
        $this->fd = eio_get_event_stream();
        $this->openFlagResolver = new OpenFlagResolver();
        $this->permissionFlagResolver = new PermissionFlagResolver();

        $this->applyConfiguration($options);
    }

    /**
     * @param array $options
     */
    protected function applyConfiguration(array $options)
    {
        $this->openFileLimiter = new OpenFileLimiter(\React\Filesystem\getOpenFileLimit($options));
        $this->options = array_merge_recursive($this->options, $options);
    }

    /**
     * @return bool
     */
    public static function isSupported()
    {
        return extension_loaded('eio');
    }

    /**
     * {@inheritDoc}
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * {@inheritDoc}
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;

        $this->typeDetectors = [
            new ConstTypeDetector($this->filesystem),
            new ModeTypeDetector($this->filesystem),
        ];
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
    public function stat($filename)
    {
        return $this->callFilesystem('eio_lstat', [$filename])->then(function ($stat) {
            $stat['atime'] = new DateTime('@' .$stat['atime']);
            $stat['mtime'] = new DateTime('@' .$stat['mtime']);
            $stat['ctime'] = new DateTime('@' .$stat['ctime']);
            return \React\Promise\resolve($stat);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function unlink($filename)
    {
        return $this->callFilesystem('eio_unlink', [$filename]);
    }

    /**
     * {@inheritDoc}
     */
    public function rename($fromFilename, $toFilename)
    {
        return $this->callFilesystem('eio_rename', [$fromFilename, $toFilename]);
    }

    /**
     * {@inheritDoc}
     */
    public function chmod($path, $mode)
    {
        return $this->callFilesystem('eio_chmod', [$path, $mode]);
    }

    /**
     * {@inheritDoc}
     */
    public function chown($path, $uid, $gid)
    {
        return $this->callFilesystem('eio_chown', [$path, $uid, $gid]);
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
     * {@inheritDoc}
     */
    public function lsStream($path)
    {
        $stream = new ObjectStream();

        $this->callFilesystem('eio_readdir', [$path, $this->options['lsFlags']], false)->then(function ($result) use ($path, $stream) {
            $this->processLsContents($path, $result, $stream);
        }, function ($error) use ($stream) {
            $stream->emit('error', [$error]);
            $stream->close();
        });

        return $stream;
    }

    /**
     * @param $basePath
     * @param $result
     * @param ObjectStream $stream
     */
    protected function processLsContents($basePath, $result, ObjectStream $stream)
    {
        if (!isset($result['dents'])) {
            $stream->close();
            return;
        }

        $promises = [];

        foreach ($result['dents'] as $entry) {
            $path = $basePath . DIRECTORY_SEPARATOR . $entry['name'];
            $node = [
                'path' => $path,
                'type' => $entry['type'],
            ];
            $promises[] = \React\Filesystem\detectType($this->typeDetectors, $node)->then(function (NodeInterface $node) use ($stream) {
                $stream->write($node);

                return \React\Promise\resolve(true);
            }, function ($error) {
                return \React\Promise\resolve(true);
            });
        }

        \React\Promise\all($promises)->then(function () use ($stream) {
            $stream->close();
        }, function ($error) use ($stream) {
            $stream->close();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function mkdir($path, $mode = self::CREATION_MODE)
    {
        return $this->callFilesystem('eio_mkdir', [
            $path,
            $this->permissionFlagResolver->resolve($mode),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function rmdir($path)
    {
        return $this->callFilesystem('eio_rmdir', [$path]);
    }

    /**
     * {@inheritDoc}
     */
    public function open($path, $flags, $mode = self::CREATION_MODE)
    {
        $eioFlags = $this->openFlagResolver->resolve($flags);
        $mode = $this->permissionFlagResolver->resolve($mode);
        return $this->openFileLimiter->open()->then(function () use ($path, $eioFlags, $mode) {
            return $this->callFilesystem('eio_open', [
                $path,
                $eioFlags,
                $mode,
            ]);
        })->otherwise(function ($error) {
            $this->openFileLimiter->close();
            return \React\Promise\reject($error);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function close($fd)
    {
        return $this->callFilesystem('eio_close', [$fd])->always(function () {
            $this->openFileLimiter->close();
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
        return $this->open($path, 'cw')->then(function ($fd) use ($content) {
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
        return $this->open($path, 'cwa')->then(function ($fd) use ($content) {
            return $this->write($fd, $content, strlen($content), 0)->always(function () use ($fd) {
                return $this->close($fd);
            });
        });
    }

    /**
     * {@inheritDoc}
     */
    public function touch($path, $mode = self::CREATION_MODE, $time = null)
    {
        return $this->stat($path)->then(function () use ($path, $time) {
            if ($time === null) {
                $time = microtime(true);
            }
            return $this->callFilesystem('eio_utime', [
                $path,
                $time,
                $time,
            ]);
        }, function () use ($path, $mode) {
            return $this->openFileLimiter->open()->then(function () use ($path, $mode) {
                return $this->callFilesystem('eio_open', [
                    $path,
                    EIO_O_CREAT,
                    $this->permissionFlagResolver->resolve($mode),
                ]);
            })->then(function ($fd) use ($path) {
                return $this->close($fd);
            });
        });
    }

    /**
     * {@inheritDoc}
     */
    public function read($fileDescriptor, $length, $offset)
    {
        return $this->callFilesystem('eio_read', [
            $fileDescriptor,
            $length,
            $offset,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function write($fileDescriptor, $data, $length, $offset)
    {
        return $this->callFilesystem('eio_write', [
            $fileDescriptor,
            $data,
            $length,
            $offset,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function readlink($path)
    {
        return $this->callFilesystem('eio_readlink', [
            $path,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function symlink($fromPath, $toPath)
    {
        return $this->callFilesystem('eio_symlink', [
            $fromPath,
            $toPath,
        ]);
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
     * @param string $function
     * @param array $args
     * @param int $errorResultCode
     * @return \React\Promise\Promise
     */
    public function callFilesystem($function, $args, $errorResultCode = -1)
    {
        return new Promise(function ($resolve, $reject) use ($function, $args, $errorResultCode) {
            if ($this->loopRunning) {
                try {
                    $resolve($this->executeDelayedCall($function, $args, $errorResultCode));
                } catch (\Exception $exception) {
                    $reject($exception);
                } catch (\Throwable $exception) {
                    $reject($exception);
                }

                return;
            }


            // Run this in a future tick to make sure all EIO calls are run within the loop
            $this->loop->futureTick(function () use ($function, $args, $errorResultCode, $resolve, $reject) {
                try {
                    $resolve($this->executeDelayedCall($function, $args, $errorResultCode));
                } catch (\Exception $exception) {
                    $reject($exception);
                } catch (\Throwable $exception) {
                    $reject($exception);
                }
            });

        });
    }

    protected function executeDelayedCall($function, $args, $errorResultCode)
    {
        $this->register();
        return new Promise(function ($resolve, $reject) use ($function, $args, $errorResultCode) {
            $args[] = EIO_PRI_DEFAULT;
            $args[] = function ($data, $result, $req) use ($resolve, $reject, $errorResultCode, $function, $args) {
                if ($result == $errorResultCode) {
                    $exception = new UnexpectedValueException(@eio_get_last_error($req));
                    $exception->setArgs($args);
                    $reject($exception);
                    return;
                }

                $resolve($result);
            };

            if (!call_user_func_array($function, $args)) {
                $name = $function;
                if (!is_string($function)) {
                    $name = get_class($function);
                }
                $exception = new RuntimeException('Unknown error calling "' . $name . '"');
                $exception->setArgs($args);
                $reject($exception);
            };
        });
    }

    protected function register()
    {
        if ($this->active) {
            return;
        }

        $this->active = true;
        $this->loop->addReadStream($this->fd, [$this, 'handleEvent']);
    }

    protected function unregister()
    {
        if (!$this->active) {
            return;
        }

        $this->active = false;
        $this->loop->removeReadStream($this->fd, [$this, 'handleEvent']);
    }

    public function handleEvent()
    {
        if ($this->workPendingCount() == 0) {
            return;
        }

        while (eio_npending()) {
            eio_poll();
        }

        if ($this->workPendingCount() == 0) {
            $this->unregister();
        }
    }

    public function workPendingCount()
    {
        return eio_nreqs() + eio_npending() + eio_nready();
    }
}
