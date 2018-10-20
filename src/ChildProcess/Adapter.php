<?php

namespace React\Filesystem\ChildProcess;

use Exception;
use React\EventLoop\LoopInterface;
use React\Filesystem\AbstractSyncAdapter;
use React\Filesystem\AdapterInterface;
use React\Filesystem\CallInvokerInterface;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\MappedTypeDetector;
use React\Filesystem\ModeTypeDetector;
use React\Filesystem\OpenFileLimiter;
use React\Filesystem\PermissionFlagResolver;
use React\Filesystem\Stream\StreamFactory;
use React\Promise\PromiseInterface;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Factory;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Payload;
use WyriHaximus\React\ChildProcess\Messenger\Messenger;
use WyriHaximus\React\ChildProcess\Pool\Options;
use WyriHaximus\React\ChildProcess\Pool\PoolInterface;

class Adapter extends AbstractSyncAdapter implements AdapterInterface
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
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;

        $this->typeDetectors = [
            MappedTypeDetector::createDefault($this->filesystem),
            new ModeTypeDetector($this->filesystem),
        ];
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
            return \React\Promise\resolve($payload->getPayload());
        }, function ($payload) {
            return \React\Promise\reject(new Exception($payload['error']['message']));
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
            'mode' => decoct($mode),
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
            $id = count($this->fileDescriptors);
            $this->fileDescriptors[$id] = $messenger;
            return $this->fileDescriptors[$id]->rpc(Factory::rpc('open', [
                'path' => $path,
                'flags' => $flags,
                'mode' => $mode,
            ]));
        })->then(function () use ($path, $flags, &$id) {
            return \React\Promise\resolve(StreamFactory::create($path, $id, $flags, $this));
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
        return $this->fileDescriptors[$fileDescriptor]->rpc(Factory::rpc('read', [
            'length' => $length,
            'offset' => $offset,
        ]))->then(function ($payload) {
            return \React\Promise\resolve(base64_decode($payload['chunk']));
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
        return $this->fileDescriptors[$fileDescriptor]->rpc(Factory::rpc('write', [
            'chunk' => base64_encode($data),
            'length' => $length,
            'offset' => $offset,
        ]));
    }

    /**
     * @param string $fd
     * @return PromiseInterface
     */
    public function close($fd)
    {
        $fileDescriptor = $this->fileDescriptors[$fd];
        unset($this->fileDescriptors[$fd]);
        return $fileDescriptor->rpc(Factory::rpc('close'))->then(function () use ($fileDescriptor) {
            return $fileDescriptor->softTerminate();
        }, function () use ($fileDescriptor) {
            return $fileDescriptor->softTerminate();
        });
    }
}
