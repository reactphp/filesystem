<?php

namespace React\Filesystem\Node;

use Evenement\EventEmitterTrait;
use React\Filesystem\AdapterInterface;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\ObjectStream;
use React\Filesystem\ObjectStreamSink;
use React\Promise\Deferred;

class Directory implements DirectoryInterface
{
    use GenericOperationTrait;
    use EventEmitterTrait;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var RecursiveInvoker
     */
    protected $recursiveInvoker;

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return $this->path . NodeInterface::DS;
    }

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
     * @param FilesystemInterface $filesystem
     * @param RecursiveInvoker $recursiveInvoker
     */
    public function __construct($path, FilesystemInterface $filesystem, RecursiveInvoker $recursiveInvoker = null)
    {
        $this->filesystem = $filesystem;
        $this->adapter = $filesystem->getAdapter();

        $this->createNameNParentFromFilename($path);
        $this->recursiveInvoker = $recursiveInvoker;
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
        return $this->adapter->ls($this->path);
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
                        });
                    }
                    break;
                case $node instanceof File:
                    $numbers['files']++;
                    $promises[] = $node->size()->then(function ($size) use (&$numbers) {
                        $numbers['size'] += $size;
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
        return $this->adapter->mkdir($this->path, $mode)->then(function () {
            $deferred = new Deferred();

            $check = function () use (&$check, $deferred) {
                $this->stat()->then(function () use ($deferred) {
                    $deferred->resolve();
                }, function () use (&$check) {
                    $this->adapter->getLoop()->addTimer(0.1, $check);
                });
            };

            $check();

            return $deferred->promise();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function remove()
    {
        return $this->adapter->rmdir($this->path);
    }


    /**
     * {@inheritdoc}
     */
    public function rename($toDirectoryName)
    {
        return $this->adapter->rename($this->path, $toDirectoryName)->then(function () use ($toDirectoryName) {
            return $this->filesystem->dir($toDirectoryName);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function createRecursive($mode = AdapterInterface::CREATION_MODE)
    {
        $parentPath = explode(DIRECTORY_SEPARATOR, $this->path);
        array_pop($parentPath);
        $parentPath = implode(DIRECTORY_SEPARATOR, $parentPath);

        $parentDirectory = $this->filesystem->dir($parentPath);
        return $parentDirectory->stat()->then(null, function () use ($parentDirectory, $mode) {
            return $parentDirectory->createRecursive($mode);
        })->then(function () use ($mode) {
            return $this->create($mode);
        })->then(function () {
            return null;
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

    /**
     * {@inheritDoc}
     */
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
        $stream = new ObjectStream();
        $closeCount = 0;
        $sourceStream->on('data', function (NodeInterface $node) use (&$closeCount, $stream) {
            $stream->write($node);
            if ($node instanceof Directory) {
                $this->streamLsIntoStream($node, $stream, $closeCount);
            }
        });

        $sourceStream->on('end', function () use (&$closeCount, $stream) {
            $this->adapter->getLoop()->addPeriodicTimer(0.01, function ($timer) use (&$closeCount, $stream) {
                if ($closeCount === 0) {
                    $this->adapter->getLoop()->cancelTimer($timer);
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

    /**
     * @param NodeInterface $node
     * @return \React\Promise\Promise
     */
    public function copy(NodeInterface $node)
    {
        return ObjectStreamSink::promise($this->copyStreaming($node));
    }

    /**
     * @param NodeInterface $node
     * @return ObjectStream
     */
    public function copyStreaming(NodeInterface $node)
    {
        if ($node instanceof DirectoryInterface) {
            return $this->copyToDirectory($node);
        }

        throw new \UnexpectedValueException('Unsupported node type');
    }

    /**
     * @param DirectoryInterface $targetNode
     * @return ObjectStream
     */
    protected function copyToDirectory(DirectoryInterface $targetNode)
    {
        $promises = [];
        $objectStream = new ObjectStream();

        $stream = $this->lsStreaming();
        $stream->on('data', function (NodeInterface $node) use ($targetNode, &$promises, $objectStream) {
            $deferred = new Deferred();
            $promises[] = $deferred->promise();

            $stream = $this->handleStreamingCopyNode($node, $targetNode);
            $stream->on('end', function () use ($deferred) {
                $deferred->resolve();
            });
            $stream->pipe($objectStream , [
                'end' => false,
            ]);
        });
        $stream->on('end', function () use ($objectStream, &$promises, $targetNode) {
            \React\Promise\all($promises)->then(function () use ($objectStream, $targetNode) {
                $objectStream->end();
            });
        });

        return $objectStream;
    }

    /**
     * @param NodeInterface $node
     * @param DirectoryInterface $targetNode
     * @return ObjectStream
     */
    protected function handleStreamingCopyNode(NodeInterface $node, DirectoryInterface $targetNode)
    {
        if ($node instanceof FileInterface) {
            return $node->copyStreaming($targetNode);
        }

        if ($node instanceof DirectoryInterface) {
            $stream = new ObjectStream();
            $newDir = $targetNode->getFilesystem()->dir($targetNode->getPath() . $node->getName());

            $newDir->stat()->then(null, function () use ($newDir) {
                return $newDir->createRecursive();
            })->then(function () use ($node, $newDir, $stream) {
                $node->copyStreaming($newDir)->pipe($stream);
            });

            return $stream;
        }
    }
}
