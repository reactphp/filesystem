<?php

namespace React\Filesystem\ChildProcess;

use DateTime;
use Exception;
use Throwable;
use React\EventLoop\LoopInterface;
use React\Filesystem\ObjectStream;
use React\Filesystem\ObjectStreamSink;
use React\Filesystem\AdapterInterface;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\MappedTypeDetector;
use React\Filesystem\ModeTypeDetector;
use React\Filesystem\OpenFileLimiter;
use React\Filesystem\TypeDetectorInterface;
use React\Filesystem\PermissionFlagResolver;
use React\Filesystem\Node\NodeInterface;
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
     * @var TypeDetectorInterface[]
     */
    protected $typeDetectors = [];

    /**
     * @var PermissionFlagResolver
     */
    protected $permissionFlagResolver;

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
     * @return bool
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

        $this->typeDetectors = [
            MappedTypeDetector::createDefault($this->filesystem),
            new ModeTypeDetector($this->filesystem),
        ];
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
            if ($payload instanceof Throwable) {
                return \React\Promise\reject($payload);
            }

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
        return $this->callFilesystem('chmod', [
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
        return $this->callFilesystem('mkdir', [
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
        return \WyriHaximus\React\ChildProcess\Messenger\Factory::parentFromClass(self::CHILD_CLASS_NAME, $this->loop)->then(function (Messenger $messenger) use ($path, $flags, $mode) {
            $this->fileDescriptors[] = $messenger;
            \end($this->fileDescriptors);
            $id = \key($this->fileDescriptors);

            return $this->fileDescriptors[$id]->rpc(Factory::rpc('open', [
                'path' => $path,
                'flags' => $flags,
                'mode' => $mode,
            ]))->then(function () use ($id) {
                return $id;
            });
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
        return $this->callFilesystem('getContents', [
            'path' => $path,
            'offset' => $offset,
            'maxlen' => $length,
        ])->then(function ($payload) {
            return \React\Promise\resolve(base64_decode($payload['chunk']));
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
        return $this->callFilesystem('putContents', [
            'path' => $path,
            'chunk' => base64_encode($content),
            'flags' => 0,
        ])->then(function ($payload) {
            return \React\Promise\resolve($payload['written']);
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
        return $this->callFilesystem('putContents', [
            'path' => $path,
            'chunk' => base64_encode($content),
            'flags' => FILE_APPEND,
        ])->then(function ($payload) {
            return \React\Promise\resolve($payload['written']);
        });
    }

    /**
     * @param string $path
     * @return PromiseInterface
     */
    public function rmdir($path)
    {
        return $this->callFilesystem('rmdir', [
            'path' => $path,
        ]);
    }

    /**
     * @param string $path
     * @return PromiseInterface
     */
    public function unlink($path)
    {
        return $this->callFilesystem('unlink', [
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
        return $this->callFilesystem('chown', [
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
        return $this->callFilesystem('stat', [
            'path' => $filename,
        ])->then(function ($stat) {
            $stat['atime'] = new DateTime('@' . $stat['atime']);
            $stat['mtime'] = new DateTime('@' . $stat['mtime']);
            $stat['ctime'] = new DateTime('@' . $stat['ctime']);
            return \React\Promise\resolve($stat);
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

        $this->callFilesystem('readdir', [
            'path' => $path,
            'flags' => $this->options['lsFlags'],
        ])->then(function ($result) use ($path, $stream) {
            $this->processLsContents($path, $result, $stream);
        });

        return $stream;
    }

    protected function processLsContents($basePath, $result, ObjectStream $stream)
    {
        $promises = [];

        foreach ($result as $entry) {
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
     * @param string $path
     * @param $mode
     * @return PromiseInterface
     */
    public function touch($path, $mode = self::CREATION_MODE)
    {
        return $this->callFilesystem('touch', [
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
        return $this->callFilesystem('rename', [
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
        return $this->callFilesystem('readlink', [
            'path' => $path,
        ])->then(function ($result) {
            return \React\Promise\resolve($result['path']);
        });
    }

    /**
     * @param string $fromPath
     * @param string $toPath
     * @return PromiseInterface
     */
    public function symlink($fromPath, $toPath)
    {
        return $this->callFilesystem('symlink', [
            'from' => $fromPath,
            'to' => $toPath,
        ])->then(function ($result) {
            return \React\Promise\resolve($result['result']);
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
}
