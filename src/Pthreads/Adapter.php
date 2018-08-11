<?php

namespace React\Filesystem\Pthreads;

use DateTime;
use Exception;
use RangeException;
use CharlotteDunois\Phoebe\Message;
use CharlotteDunois\Phoebe\Pool;
use CharlotteDunois\Phoebe\Worker;
use React\EventLoop\LoopInterface;
use React\Filesystem\InstantInvoker;
use React\Filesystem\AdapterInterface;
use React\Filesystem\CallInvokerInterface;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\TypeDetectorInterface;
use React\Filesystem\MappedTypeDetector;
use React\Filesystem\ModeTypeDetector;
use React\Filesystem\PermissionFlagResolver;
use React\Promise\Promise;

class Adapter implements AdapterInterface
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var \CharlotteDunois\Phoebe\Pool
     */
    protected $pool;

    /**
     * @var CallInvokerInterface
     */
    protected $invoker;

    /**
     * @var PermissionFlagResolver
     */
    protected $permissionFlagResolver;

    /**
     * @var TypeDetectorInterface
     */
    protected $typeDetectorLs;

    /**
     * @var TypeDetectorInterface
     */
    protected $typeDetector;

    /**
     * @var array
     */
    protected $fileDescriptors = [];

    /**
     * @var array
     */
    protected $options = [
        'lsFlags' => SCANDIR_SORT_NONE,
    ];

    /**
     * @var int
     */
    protected $workCounter = 0;

    /**
     * @inheritDoc
     */
    public function __construct(LoopInterface $loop, array $options = [])
    {
        $this->loop = $loop;

        if(!empty($options['workerPool']) && $options['workerPool'] instanceof Pool) {
            $this->pool = $options['workerPool'];
            $this->workCounter = 9001;
        } else {
            $this->pool = new Pool($loop, ((int) ($options['workerSize'] ?? 5)));
            $this->pool->cancelTimer();
        }

        $this->invoker = \React\Filesystem\getInvoker($this, $options, 'invoker', InstantInvoker::class);
        $this->permissionFlagResolver = new PermissionFlagResolver();
    }

    /**
     * Returns the used Phoebe pool.
     * @return Pool
     */
    public function getPool()
    {
        return $this->pool;
    }

    /**
     * Destroys the pool manager and thus free the event loop from its timer.
     */
    public function destroy()
    {
        $this->pool->destroy();
    }

    /**
     * @return boolean
     */
    public static function isSupported()
    {
        return extension_loaded('pthreads') && class_exists('CharlotteDunois\\Phoebe\\Pool');
    }

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

        $this->typeDetectorLs = MappedTypeDetector::createDefault($this->filesystem);
        $this->typeDetector = new ModeTypeDetector($this->filesystem);
    }

    /**
     * {@inheritDoc}
     */
    public function getInvoker()
    {
        return $this->invoker;
    }

    /**
     * @param CallInvokerInterface $invoker
     * @return void
     */
    public function setInvoker(CallInvokerInterface $invoker)
    {
        $this->invoker = $invoker;
    }

    /**
     * @inheritDoc
     */
    public function callFilesystem($function, $args, $errorResultCode = -1)
    {
        $this->register();
        $yarn = new Yarn($function, $args);

        return $this->pool->submitTask($yarn)->then(function ($value) {
            $this->unregister();
            return $value;
        }, function ($error) {
            $this->unregister();
            throw $error;
        });
    }

    /**
     * @param string $path
     * @param int $mode
     * @return PromiseInterface
     */
    public function chmod($path, $mode)
    {
        return $this->invoker->invokeCall('chmod', [
            $path,
            $mode,
        ]);
    }

    /**
     * @param string $path
     * @param mixed  $mode
     * @return PromiseInterface
     */
    public function mkdir($path, $mode = self::CREATION_MODE)
    {
        return $this->invoker->invokeCall('mkdir', [
            $path,
            $this->permissionFlagResolver->resolve($mode),
        ]);
    }

    /**
     * @param string $path
     * @return PromiseInterface
     */
    public function rmdir($path)
    {
        return $this->invoker->invokeCall('rmdir', [
            $path,
        ]);
    }

    /**
     * @param string $path
     * @return PromiseInterface
     */
    public function unlink($path)
    {
        return $this->invoker->invokeCall('unlink', [
            $path,
        ]);
    }

    /**
     * @param string $path
     * @param int $uid
     * @param int $gid
     * @return PromiseInterface
     */
    public function chown($path, $uid, $gid)
    {
        return $this->invoker->invokeCall('chown', [
            'path' => $path,
            'uid' => $uid,
            'gid' => $gid,
        ]);
    }

    /**
     * @param string $filename
     * @return PromiseInterface
     */
    public function stat($filename)
    {
        return $this->invoker->invokeCall('stat', [
            'path' => $filename,
        ])->then(function ($stat) {
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
        return $this->invoker->invokeCall('readdir', [
            'path' => $path,
            'flags' => $this->options['lsFlags'],
        ])->then(function ($result) use ($path) {
            $basePath = $path;
            $promises = [];

            foreach ($result as $entry) {
                $path = $basePath . DIRECTORY_SEPARATOR . $entry['name'];
                $node = [
                    'path' => $path,
                    'type' => $entry['type'],
                ];
                $promises[] = $this->typeDetectorLs->detect($node)->otherwise(function () use ($path) {
                    return $this->detectType($path);
                });
            }
    
            return \React\Promise\all($promises);
        });
    }

    /**
     * @param string $path
     * @param mixed  $mode
     * @return PromiseInterface
     */
    public function touch($path, $mode = self::CREATION_MODE)
    {
        return $this->invoker->invokeCall('touch', [
            'path' => $path,
            'mode' => $this->permissionFlagResolver->resolve($mode),
        ]);
    }

    /**
     * @param string $path
     * @param string $flags
     * @param mixed  $mode
     * @return PromiseInterface
     */
    public function open($path, $flags, $mode = self::CREATION_MODE)
    {
        $needle = new Needle($path, $flags);
        $worker = $this->pool->submit($needle);

        $this->register();
        return $this->waitForEvent('rfs-fd-ready', $needle->id)->then(function ($value) {
            $this->unregister();
            return $value;
        }, function ($error) {
            $this->unregister();
            throw $error;
        })->then(function () use ($path, $flags, $needle, $worker) {
            $this->fileDescriptors[$needle->id] = array(
                'needle' => $needle,
                'worker' => $worker,
            );

            return $needle->id;
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

        $this->register();
        $wait = $this->waitForEvent('rfs-fd-read', $this->fileDescriptors[$fileDescriptor]['needle']->id)->then(function ($value) {
            $this->unregister();
            return $value;
        }, function ($error) {
            $this->unregister();
            throw $error;
        });

        $this->fileDescriptors[$fileDescriptor]['needle']->call($this->fileDescriptors[$fileDescriptor]['worker'], 'rfs-req-read', [
            'length' => $length,
            'offset' => $offset,
        ]);

        return $wait;
    }

    /**
     * @param string $fileDescriptor
     * @param string $data
     * @param int $length
     * @param int $offset
     * @return PromiseInterface
     */
    public function write($fileDescriptor, $data, $length, $offset)
    {
        if (empty($this->fileDescriptors[$fileDescriptor])) {
            return \React\Promise\reject(new Exception('Unknown file descriptor'));
        }

        $this->register();
        $wait = $this->waitForEvent('rfs-fd-write', $this->fileDescriptors[$fileDescriptor]['needle']->id)->then(function ($value) {
            $this->unregister();
            return $value;
        }, function ($error) {
            $this->unregister();
            throw $error;
        });

        $this->fileDescriptors[$fileDescriptor]['needle']->call($this->fileDescriptors[$fileDescriptor]['worker'], 'rfs-req-write', [
            'chunk' => $data,
            'length' => $length,
            'offset' => $offset,
        ]);

        return $wait;
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

        $this->register();
        $wait = $this->waitForEvent('rfs-fd-close', $fd['needle']->id)->then(function ($value) {
            $this->unregister();
            return $value;
        }, function ($error) {
            $this->unregister();
            throw $error;
        });

        $fd['needle']->call($fd['worker'], 'rfs-req-close', []);
        return $wait;
    }

    /**
     * @param string $fromPath
     * @param string $toPath
     * @return PromiseInterface
     */
    public function rename($fromPath, $toPath)
    {
        return $this->invoker->invokeCall('rename', [
            $fromPath,
            $toPath,
        ]);
    }

    /**
     * @param string $path
     * @return PromiseInterface
     */
    public function readlink($path)
    {
        return $this->invoker->invokeCall('readlink', [
            $path,
        ]);
    }

    /**
     * @param string $fromPath
     * @param string $toPath
     * @return PromiseInterface
     */
    public function symlink($fromPath, $toPath)
    {
        return $this->invoker->invokeCall('symlink', [
            $fromPath,
            $toPath,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function detectType($path)
    {
        return $this->stat($path)->then(function ($stat) use ($path) {
            $stat['path'] = $path;
            return $this->typeDetector->detect($stat);
        });
    }

    /**
     * Registers work and possibly the timer.
     */
    protected function register()
    {
        if($this->workCounter <= 0) {
            $this->pool->attachTimer();
        }

        $this->workCounter++;
    }

    /**
     * Unregisters work and possibly the timer.
     */
    protected function unregister()
    {
        $this->workCounter--;
        if($this->workCounter <= 0) {
            $this->pool->cancelTimer();
        }
    }

    /**
     * @param string $event
     * @param int    $id
     */
    protected function waitForEvent($event, $id) {
        return (new Promise(function (callable $resolve, callable $reject) use ($event, $id) {
            $timer = null;

            $listener = function (Worker $worker, Message $message) use ($event, $id, $resolve, $reject, &$listener, &$timer) {
                if ($message->getType() === $event) {
                    $payload = $message->getPayload();
                    if ($payload['id'] === $id) {
                        if ($timer) {
                            $this->loop->cancelTimer($timer);
                            $timer = null;
                        }

                        $this->pool->removeListener('message', $listener);

                        if (!empty($payload['error'])) {
                            return $reject(Message::importException($payload['error']));
                        }

                        $resolve(($payload['result'] ?? null));
                    }
                }
            };

            $timer = $this->loop->addTimer(($this->options['waitingTimeout'] ?? 30), function () use ($reject, &$listener) {
                $this->pool->removeListener('message', $listener);
                $reject(new RangeException('Operation took too long'));
            });

            $this->pool->on('message', $listener);
        }));
    }
}
