<?php

namespace React\Filesystem\ChildProcess;

use DateTime;
use Exception;
use React\EventLoop\LoopInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\CallInvokerInterface;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\MappedTypeDetector;
use React\Filesystem\ModeTypeDetector;
use React\Filesystem\OpenFileLimiter;
use React\Filesystem\PermissionFlagResolver;
use React\Filesystem\TypeDetectorInterface;
use React\Promise\PromiseInterface;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Factory;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Payload;
use WyriHaximus\React\ChildProcess\Messenger\Messenger;
use WyriHaximus\React\ChildProcess\Pool\Options;
use WyriHaximus\React\ChildProcess\Pool\PoolInterface;

class Adapter implements AdapterInterface
{
    const DEFAULT_POOL     = 'WyriHaximus\React\ChildProcess\Pool\Factory\Flexible';
    const POOL_INTERFACE   = 'WyriHaximus\React\ChildProcess\Pool\PoolFactoryInterface';
    const CHILD_CLASS_NAME = 'React\Filesystem\ChildProcess\Process';

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var PoolInterface
     */
    protected $pool;

    /**
     * @var OpenFileLimiter
     */
    protected $openFileLimiter;
    
    /**
     * @var array
     */
    protected $fileDescriptors = [];

    /**
     * @var TypeDetectorInterface
     */
    protected $typeDetectorLs;

    /**
     * @var TypeDetectorInterface
     */
    protected $typeDetector;

    /**
     * @var PermissionFlagResolver
     */
    protected $permissionFlagResolver;

    /**
     * @var CallInvokerInterface
     */
    protected $invoker;

    /**
     * @var array
     */
    protected $options = [
        'lsFlags' => SCANDIR_SORT_NONE,
    ];

    /**
     * Adapter constructor.
     * @param LoopInterface $loop
     * @param array $options
     */
    public function __construct(LoopInterface $loop, array $options = [])
    {
        $this->loop = $loop;

        $this->invoker = \React\Filesystem\getInvoker($this, $options, 'invoker', 'React\Filesystem\InstantInvoker');
        $this->openFileLimiter = new OpenFileLimiter(\React\Filesystem\getOpenFileLimit($options));
        $this->permissionFlagResolver = new PermissionFlagResolver();

        $this->setUpPool($options);

        $this->options = array_merge_recursive($this->options, $options);
    }

    protected function setUpPool($options)
    {
        $poolOptions = [
            Options::MIN_SIZE => 0,
            Options::MAX_SIZE => 50,
            Options::TTL => 3,
        ];
        $poolClass = static::DEFAULT_POOL;

        if (isset($options['pool']['class']) && is_subclass_of($options['pool']['class'], static::POOL_INTERFACE)) {
            $poolClass = $options['pool']['class'];
        }

        call_user_func_array($poolClass . '::createFromClass', [
            self::CHILD_CLASS_NAME,
            $this->loop,
            $poolOptions,
        ])->then(function (PoolInterface $pool) {
            $this->pool = $pool;
        });
    }

    /**
     * @return boolean
     */
    public static function isSupported()
    {
        return substr(strtolower(PHP_OS), 0, 3) !== 'win' && function_exists('proc_open');
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
     * @param string $function
     * @param array $args
     * @param int $errorResultCode
     * @return PromiseInterface
     */
    public function callFilesystem($function, $args, $errorResultCode = -1)
    {
        return $this->pool->rpc(Factory::rpc($function, $args))->then(function (Payload $payload) {
            return $payload->getPayload();
        }, function ($payload) {
            throw new Exception($payload['error']['message']);
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
            'path' => $path,
            'mode' => $mode,
        ]);
    }

    /**
     * @param string $path
     * @param $mode
     * @return PromiseInterface
     */
    public function mkdir($path, $mode = self::CREATION_MODE)
    {
        return $this->invoker->invokeCall('mkdir', [
            'path' => $path,
            'mode' => decoct($this->permissionFlagResolver->resolve($mode)),
        ]);
    }

    /**
     * @param string $path
     * @param string $flags
     * @param $mode
     * @return PromiseInterface
     */
    public function open($path, $flags, $mode = self::CREATION_MODE)
    {
        $id = null;
        return \WyriHaximus\React\ChildProcess\Messenger\Factory::parentFromClass(self::CHILD_CLASS_NAME, $this->loop)->then(function (Messenger $messenger) use (&$id, $path, $flags, $mode) {
            $id = bin2hex(random_bytes(10));
            $this->fileDescriptors[$id] = $messenger;
            
            return $messenger->rpc(Factory::rpc('open', [
                'path' => $path,
                'flags' => $flags,
                'mode' => $mode,
            ]));
        })->then(function () use (&$id) {
            return $id;
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

        return $this->fileDescriptors[$fileDescriptor]->rpc(Factory::rpc('read', [
            'length' => $length,
            'offset' => $offset,
        ]))->then(function ($payload) {
            return base64_decode($payload['chunk']);
        });
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

        return $this->fileDescriptors[$fileDescriptor]->rpc(Factory::rpc('write', [
            'chunk' => base64_encode($data),
            'length' => $length,
            'offset' => $offset,
        ]))->then(function ($payload) {
            return $payload['written'];
        });
    }

    /**
     * @param string $fd
     * @return PromiseInterface
     */
    public function close($fd)
    {
        if (empty($this->fileDescriptors[$fd])) {
            return \React\Promise\reject(new Exception('Unknown file descriptor'));
        }

        $fileDescriptor = $this->fileDescriptors[$fd];
        unset($this->fileDescriptors[$fd]);
        return $fileDescriptor->rpc(Factory::rpc('close'))->then(function () use ($fileDescriptor) {
            return $fileDescriptor->softTerminate();
        }, function () use ($fileDescriptor) {
            return $fileDescriptor->softTerminate();
        });
    }
    
    /**
     * @param string $path
     * @return PromiseInterface
     */
    public function rmdir($path)
    {
        return $this->invoker->invokeCall('rmdir', [
            'path' => $path,
        ]);
    }

    /**
     * @param string $path
     * @return PromiseInterface
     */
    public function unlink($path)
    {
        return $this->invoker->invokeCall('unlink', [
            'path' => $path,
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
     * @param $mode
     * @return PromiseInterface
     */
    public function touch($path, $mode = self::CREATION_MODE)
    {
        return $this->invoker->invokeCall('touch', [
            'path' => $path,
            'mode' => decoct($this->permissionFlagResolver->resolve($mode)),
        ]);
    }

    /**
     * @param string $fromPath
     * @param string $toPath
     * @return PromiseInterface
     */
    public function rename($fromPath, $toPath)
    {
        return $this->invoker->invokeCall('rename', [
            'from' => $fromPath,
            'to' => $toPath,
        ]);
    }

    /**
     * @param string $path
     * @return PromiseInterface
     */
    public function readlink($path)
    {
        return $this->invoker->invokeCall('readlink', [
            'path' => $path,
        ])->then(function ($result) {
            return $result['path'];
        });
    }

    /**
     * @param string $target
     * @param string $link
     * @return PromiseInterface
     */
    public function symlink($target, $link)
    {
        return $this->invoker->invokeCall('symlink', [
            'from' => $target,
            'to' => $link,
        ])->then(function ($result) {
            return $result['result'];
        });
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
}
