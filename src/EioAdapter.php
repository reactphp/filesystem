<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Filesystem\Node\Directory;
use React\Filesystem\Node\File;
use React\Filesystem\Node\NodeInterface;
use React\Promise\Deferred;
use React\Filesystem\Eio;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;

class EioAdapter implements AdapterInterface
{
    /**
     * @var bool
     */
    protected $active = false;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var Eio\OpenFlagResolver
     */
    protected $openFlagResolver;

    /**
     * @var Eio\PermissionFlagResolver
     */
    protected $permissionFlagResolver;

    /**
     * @var PooledInvoker
     */
    protected $invoker;

    /**
     * @var QueuedInvoker
     */
    protected $readDirInvoker;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var TypeDetectorInterface[]
     */
    protected $typeDetectors;

    protected $openFileLimiter;

    public function __construct(LoopInterface $loop, array $options = [])
    {
        eio_init();
        $this->loop = $loop;
        $this->fd = eio_get_event_stream();
        $this->openFlagResolver = new Eio\OpenFlagResolver();
        $this->permissionFlagResolver = new Eio\PermissionFlagResolver();

        $this->applyConfiguration($options);
    }

    /**
     * @param array $options
     */
    protected function applyConfiguration(array $options)
    {
        $this->invoker = $this->getInvoker($options, 'invoker', 'React\Filesystem\PooledInvoker');
        $this->readDirInvoker = $this->getInvoker($options, 'read_dir_invoker', 'React\Filesystem\QueuedInvoker');
        $this->openFileLimiter = new OpenFileLimiter($this->getOpenFileLimit($options));
    }

    /**
     * @param array $options
     * @param string $fallback
     * @return CallInvokerInterface
     */
    protected function getInvoker(array $options, $key, $fallback)
    {
        if (isset($options[$key]) && $options[$key] instanceof CallInvokerInterface) {
            return $options[$key];
        }

        return new $fallback($this);
    }

    /**
     * @param array $options
     * @return int
     */
    protected function getOpenFileLimit(array $options)
    {
        if (isset($options['open_file_limit'])) {
            return (int)$options['open_file_limit'];
        }

        return OpenFileLimiter::DEFAULT_LIMIT;
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
    public function setInvoker(CallInvokerInterface $invoker)
    {
        $this->invoker = $invoker;
    }

    /**
     * {@inheritDoc}
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;

        $this->typeDetectors = [
            new Eio\ConstTypeDetector($this->filesystem),
            new ModeTypeDetector($this->filesystem),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function setReadDirInvoker(CallInvokerInterface $invoker)
    {
        $this->readDirInvoker = $invoker;
    }

    /**
     * {@inheritDoc}
     */
    public function stat($filename)
    {
        return $this->invoker->invokeCall('eio_stat', [$filename]);
    }

    /**
     * {@inheritDoc}
     */
    public function unlink($filename)
    {
        return $this->invoker->invokeCall('eio_unlink', [$filename]);
    }

    /**
     * {@inheritDoc}
     */
    public function rename($fromFilename, $toFilename)
    {
        return $this->invoker->invokeCall('eio_rename', [$fromFilename, $toFilename]);
    }

    /**
     * {@inheritDoc}
     */
    public function chmod($path, $mode)
    {
        return $this->invoker->invokeCall('eio_chmod', [$path, $mode]);
    }

    /**
     * {@inheritDoc}
     */
    public function chown($path, $uid, $gid)
    {
        return $this->invoker->invokeCall('eio_chown', [$path, $uid, $gid]);
    }

    /**
     * {@inheritDoc}
     */
    public function ls($path, $flags = EIO_READDIR_STAT_ORDER)
    {
        $stream = new ObjectStream();

        $this->readDirInvoker->invokeCall('eio_readdir', [$path, $flags], false)->then(function ($result) use ($path, $stream) {
            $this->processLsContents($path, $result, $stream);
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
            $promises[] = $this->processLsDent($node, $stream);
        }

        \React\Promise\all($promises)->then(function () use ($stream) {
            $stream->close();
        });
    }

    /**
     * @param array $node
     * @param ObjectStream $stream
     * @return PromiseInterface
     */
    protected function processLsDent(array $node, ObjectStream $stream)
    {
        $promiseChain = new RejectedPromise();
        foreach ($this->typeDetectors as $detector) {
            $promiseChain = $promiseChain->otherwise(function () use ($node, $detector) {
                return $detector->detect($node);
            });
        }

        return $promiseChain->then(function ($callable) use ($node, $stream) {
            $stream->emit('data', [
                $callable($node['path']),
            ]);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function mkdir($path, $mode = self::CREATION_MODE)
    {
        return $this->invoker->invokeCall('eio_mkdir', [
            $path,
            $this->permissionFlagResolver->resolve($mode),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function rmdir($path)
    {
        return $this->invoker->invokeCall('eio_rmdir', [$path]);
    }

    /**
     * {@inheritDoc}
     */
    public function open($path, $flags, $mode = self::CREATION_MODE)
    {
        $flags = $this->openFlagResolver->resolve($flags);
        $mode = $this->permissionFlagResolver->resolve($mode);
        return $this->openFileLimiter->open()->then(function () use ($path, $flags, $mode) {
            return $this->invoker->invokeCall('eio_open', [
                $path,
                $flags,
                $mode,
            ]);
        })->then(function ($fileDescriptor) use ($path, $flags) {
            return Eio\StreamFactory::create($path, $fileDescriptor, $flags, $this);
        }, function ($error) {
            $this->openFileLimiter->close();
            return \React\Promise\reject($error);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function close($fd)
    {
        return $this->invoker->invokeCall('eio_close', [$fd])->then(function ($result) {
            $this->openFileLimiter->close();
            return \React\Promise\resolve($result);
        }, function ($error) {
            $this->openFileLimiter->close();
            return \React\Promise\reject($error);
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
            return $this->invoker->invokeCall('eio_utime', [
                $path,
                $time,
                $time,
            ]);
        }, function () use ($path, $mode) {
            return $this->invoker->invokeCall('eio_open', [
                $path,
                EIO_O_CREAT,
                $this->permissionFlagResolver->resolve($mode),
            ])->then(function ($fd) use ($path) {
                return $this->close($fd);
            });
        });
    }

    /**
     * {@inheritDoc}
     */
    public function read($fileDescriptor, $length, $offset)
    {
        return $this->invoker->invokeCall('eio_read', [
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
        return $this->invoker->invokeCall('eio_write', [
            $fileDescriptor,
            $data,
            $length,
            $offset,
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
        $deferred = new Deferred();

        // Run this in a future tick to make sure all EIO calls are run within the loop
        $this->loop->futureTick(function () use ($function, $args, $errorResultCode, $deferred) {
            $this->executeDelayedCall($function, $args, $errorResultCode, $deferred);
        });

        return $deferred->promise();
    }

    protected function executeDelayedCall($function, $args, $errorResultCode, Deferred $deferred)
    {
        $this->register();
        $args[] = EIO_PRI_DEFAULT;
        $args[] = function ($data, $result, $req) use ($deferred, $errorResultCode, $function, $args) {
            if ($result == $errorResultCode) {
                $exception = new Eio\UnexpectedValueException(@eio_get_last_error($req));
                $exception->setArgs($args);
                $deferred->reject($exception);
                return;
            }

            $deferred->resolve($result);
        };

        if (!call_user_func_array($function, $args)) {
            $name = $function;
            if (!is_string($function)) {
                $name = get_class($function);
            }
            $exception = new Eio\RuntimeException('Unknown error calling "' . $name . '"');
            $exception->setArgs($args);
            $deferred->reject($exception);
        };
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
