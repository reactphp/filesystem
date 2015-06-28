<?php

namespace React\Filesystem\Node;

use Evenement\EventEmitterTrait;
use React\EventLoop\Timer\TimerInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\ObjectStreamSink;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;

class Directory implements NodeInterface, DirectoryInterface, GenericOperationInterface
{

    use GenericOperationTrait;
    use EventEmitterTrait;

    protected $recursiveInvoker;

    /**
     * @return RecursiveInvoker
     */
    protected function getRecursiveInvoker()
    {
        if ($this->recursiveInvoker instanceof RecursiveInvoker) {
            return $this->recursiveInvoker;
        }

        $this->recursiveInvoker = new RecursiveInvoker($this);
        return $this->recursiveInvoker;
    }

    /**
     * @param $path
     * @param AdapterInterface $filesystem
     * @param RecursiveInvoker $recursiveInvoker
     */
    public function __construct($path, AdapterInterface $filesystem, RecursiveInvoker $recursiveInvoker = null)
    {
        $this->path = $path;
        $this->filesystem = $filesystem;
        $this->recursiveInvoker = $recursiveInvoker;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->getPath();
    }

    /**
     * {@inheritDoc}
     */
    public function ls()
    {
        return ObjectStreamSink::promise($this->lsStreaming());
    }

    /**
     * {@inheritDoc}
     */
    public function lsStreaming()
    {
        return $this->filesystem->ls($this->path);
    }

    /**
     * {@inheritDoc}
     */
    public function size($recursive = false)
    {
        return $this->ls()->then(function ($result) use ($recursive) {
            return $this->processSizeContents($result, $recursive);
        });
    }

    /**
     * @param $nodes
     * @param $recursive
     * @return \React\Promise\Promise
     */
    protected function processSizeContents($nodes, $recursive)
    {
        $numbers = [
            'directories' => 0,
            'files' => 0,
            'size' => 0,
        ];

        $promises = [];
        foreach ($nodes as $node) {
            switch (true) {
                case $node instanceof Directory:
                    $numbers['directories']++;
                    if ($recursive) {
                        $promises[] = $node->size()->then(function ($size) use (&$numbers) {
                            $numbers['directories'] += $size['directories'];
                            $numbers['files'] += $size['files'];
                            $numbers['size'] += $size['size'];
                            return new FulfilledPromise();
                        });
                    }
                    break;
                case $node instanceof File:
                    $numbers['files']++;
                    $promises[] = $node->size()->then(function ($size) use (&$numbers) {
                        $numbers['size'] += $size;
                        return new FulfilledPromise();
                    });
                    break;
            }
        }

        return \React\Promise\all($promises)->then(function () use (&$numbers) {
            return $numbers;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function create($mode = AdapterInterface::CREATION_MODE)
    {
        return $this->filesystem->mkdir($this->path, $mode)->then(function () {
            $deferred = new Deferred();

            $check = function () use (&$check, $deferred) {
                $this->stat()->then(function () use ($deferred) {
                    $deferred->resolve();
                }, function () use (&$check) {
                    $this->filesystem->getLoop()->addTimer(0.1, $check);
                });
            };

            $this->filesystem->getLoop()->addTimer(0.1, $check);

            return $deferred->promise();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function remove()
    {
        return $this->filesystem->rmdir($this->path);
    }

    /**
     * {@inheritDoc}
     */
    public function createRecursive($mode = AdapterInterface::CREATION_MODE)
    {
        $parentPath = explode(DIRECTORY_SEPARATOR, $this->path);
        array_pop($parentPath);
        $parentPath = implode(DIRECTORY_SEPARATOR, $parentPath);

        $parentDirectory = new Directory($parentPath, $this->filesystem);
        $parentDirectory->stat()->then(null, function () use ($parentDirectory, $mode) {
            return $parentDirectory->createRecursive($mode);
        })->then(function () use ($mode) {
            return $this->create($mode);
        })->then(function () {
            return new FulfilledPromise();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function chmodRecursive($mode)
    {
        return $this->getRecursiveInvoker()->execute('chmod', [$mode]);
    }

    /**
     * {@inheritDoc}
     */
    public function chownRecursive($uid = -1, $gid = -1)
    {
        return $this->getRecursiveInvoker()->execute('chown', [$uid, $gid]);
    }

    /**
     * {@inheritDoc}
     */
    public function removeRecursive()
    {
        return $this->getRecursiveInvoker()->execute('remove', []);
    }

    /**
     * {@inheritDoc}
     */
    public function sizeRecursive()
    {
        return $this->size(true);
    }

    /**
     * {@inheritDoc}
     */
    public function lsRecursive()
    {
        return ObjectStreamSink::promise($this->lsRecursiveStreaming());
    }

    public function lsRecursiveStreaming()
    {
        return $this->processLsRecursiveContents($this->lsStreaming());
    }

    /**
     * @param $sourceStream
     * @return Stream
     */
    protected function processLsRecursiveContents($sourceStream)
    {
        $stream = new Stream();
        $closeCount = 0;
        $sourceStream->on('data', function (NodeInterface $node) use (&$closeCount, $stream) {
            if ($node instanceof Directory || $node instanceof File) {
                $stream->emit('data', [$node]);
            }
            if ($node instanceof Directory) {
                $this->streamLsIntoStream($node, $stream, $closeCount);
            }
        });

        $sourceStream->on('end', function () use (&$closeCount, $stream) {
            $this->filesystem->getLoop()->addPeriodicTimer(0.01, function (TimerInterface $timer) use (&$closeCount, $stream) {
                if ($closeCount === 0) {
                    $timer->cancel();
                    $stream->close();
                }
            });
        });

        return $stream;
    }

    /**
     * @param DirectoryInterface $node
     * @param $stream
     * @param $closeCount
     */
    protected function streamLsIntoStream(DirectoryInterface $node, $stream, &$closeCount)
    {
        $closeCount++;
        $nodeStream = $node->lsRecursiveStreaming();
        $nodeStream->on('end', function () use (&$closeCount) {
            $closeCount--;
        });
        $nodeStream->pipe($stream, [
            'end' => false,
        ]);
    }
}
