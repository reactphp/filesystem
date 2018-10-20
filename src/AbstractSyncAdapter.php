<?php

namespace React\Filesystem;

use DateTime;
use LogicException;
use React\Filesystem\Node\NodeInterface;
use React\Promise\PromiseInterface;

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
        return \React\Promise\reject(new LogicException('Not implemented'));
    }

    /**
     * @param $fileDescriptor
     * @param int $length
     * @param int $offset
     * @return PromiseInterface
     */
    public function read($fileDescriptor, $length, $offset)
    {
        return \React\Promise\reject(new LogicException('Not implemented'));
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
        return \React\Promise\reject(new LogicException('Not implemented'));
    }

    /**
     * @param resource $fd
     * @return PromiseInterface
     */
    public function close($fd)
    {
        return \React\Promise\reject(new LogicException('Not implemented'));
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
