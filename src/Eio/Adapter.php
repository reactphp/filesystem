<?php

namespace React\Filesystem\Eio;

use DateTime;
use React\EventLoop\LoopInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\CallInvokerInterface;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\ModeTypeDetector;
use React\Filesystem\Node\NodeInterface;
use React\Filesystem\ObjectStream;
use React\Filesystem\OpenFileLimiter;
use React\Filesystem\PermissionFlagResolver;
use React\Filesystem\Stream\StreamFactory;
use React\Filesystem\TypeDetectorInterface;
use React\Promise\Deferred;

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
     * @var CallInvokerInterface
     */
    protected $invoker;

    /**
     * @var CallInvokerInterface
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
        $this->invoker = \React\Filesystem\getInvoker($this, $options, 'invoker', 'React\Filesystem\InstantInvoker');
        $this->readDirInvoker = \React\Filesystem\getInvoker($this, $options, 'read_dir_invoker', 'React\Filesystem\InstantInvoker');
        $this->openFileLimiter = new OpenFileLimiter(\React\Filesystem\getOpenFileLimit($options));
        $this->options = array_merge_recursive($this->options, $options);
    }

    /**
     * @return boolean
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
            new ConstTypeDetector($this->filesystem),
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
        return $this->invoker->invokeCall('eio_lstat', [$filename])->then(function ($stat) {
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
    public function ls($path)
    {
        $stream = new ObjectStream();

        $this->readDirInvoker->invokeCall('eio_readdir', [$path, $this->options['lsFlags']], false)->then(function ($result) use ($path, $stream) {
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
            $promises[] = \React\Filesystem\detectType($this->typeDetectors, $node)->then(function (NodeInterface $node) use ($stream) {
                $stream->write($node);
            });
        }

        \React\Promise\all($promises)->then(function () use ($stream) {
            $stream->close();
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
        $eioFlags = $this->openFlagResolver->resolve($flags);
        $mode = $this->permissionFlagResolver->resolve($mode);
        return $this->openFileLimiter->open()->then(function () use ($path, $eioFlags, $mode) {
            return $this->invoker->invokeCall('eio_open', [
                $path,
                $eioFlags,
                $mode,
            ]);
        })->then(function ($fileDescriptor) use ($path, $flags) {
            return StreamFactory::create($path, $fileDescriptor, $flags, $this);
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
        return $this->invoker->invokeCall('eio_close', [$fd])->always(function () {
            $this->openFileLimiter->close();
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
            return $this->openFileLimiter->open()->then(function () use ($path, $mode) {
                return $this->invoker->invokeCall('eio_open', [
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
     * {@inheritDoc}
     */
    public function readlink($path)
    {
        return $this->invoker->invokeCall('eio_readlink', [
            $path,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function symlink($fromPath, $toPath)
    {
        return $this->invoker->invokeCall('eio_symlink', [
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
        $deferred = new Deferred();

        if ($this->loopRunning) {
            $this->executeDelayedCall($function, $args, $errorResultCode, $deferred);
            return $deferred->promise();
        }

        // Run this in a future tick to make sure all EIO calls are run within the loop
        $this->loop->futureTick(function () use ($function, $args, $errorResultCode, $deferred) {
            $this->loopRunning = true;
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
                $exception = new UnexpectedValueException(@eio_get_last_error($req));
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
            $exception = new RuntimeException('Unknown error calling "' . $name . '"');
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
