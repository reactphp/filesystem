<?php

namespace React\Filesystem;

use DateTimeImmutable;
use React\Filesystem\Node\NodeInterface;
use React\Filesystem\Stream\StreamFactory;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use React\Promise\RejectedPromise;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Factory;
use WyriHaximus\React\ChildProcess\Messenger\Messenger;

abstract class AbstractSyncAdapter implements AdapterInterface
{
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
            $stat['atime'] = new DateTimeImmutable('@' . $stat['atime']);
            $stat['mtime'] = new DateTimeImmutable('@' . $stat['mtime']);
            $stat['ctime'] = new DateTimeImmutable('@' . $stat['ctime']);
            return \React\Promise\resolve($stat);
        });
    }

    /**
     * @param string $path
     * @return PromiseInterface
     */
    public function ls($path)
    {
        $stream = new ObjectStream();

        $this->invoker->invokeCall('readdir', [
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

                return new FulfilledPromise();
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
        return $this->invoker->invokeCall('touch', [
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
        return new RejectedPromise();
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
     * @param $fileDescriptor
     * @param int $length
     * @param int $offset
     * @return PromiseInterface
     */
    public function read($fileDescriptor, $length, $offset)
    {
        return new RejectedPromise();
        return $this->fileDescriptors[$fileDescriptor]->rpc(Factory::rpc('read', [
            'length' => $length,
            'offset' => $offset,
        ]))->then(function ($payload) {
            return \React\Promise\resolve($payload['chunk']);
        });
    }

    /**
     * @param $fileDescriptor
     * @param string $data
     * @param int $length
     * @param int $offset
     * @return PromiseInterface
     */
    public function write($fileDescriptor, $data, $length, $offset)
    {
        return new RejectedPromise();
        return $this->fileDescriptors[$fileDescriptor]->rpc(Factory::rpc('write', [
            'chunk' => $data,
            'length' => $length,
            'offset' => $offset,
        ]));
    }

    /**
     * @param resource $fd
     * @return PromiseInterface
     */
    public function close($fd)
    {
        return new RejectedPromise();
        $fileDescriptor = $this->fileDescriptors[$fd];
        unset($this->fileDescriptors[$fd]);
        return $fileDescriptor->rpc(Factory::rpc('close'))->then(function () use ($fileDescriptor) {
            return $fileDescriptor->softTerminate();
        }, function () use ($fileDescriptor) {
            return $fileDescriptor->softTerminate();
        });
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
        return $this->invoker->invokeCall('symlink', [
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
