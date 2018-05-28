<?php

namespace React\Filesystem\Node;

use React\Filesystem\AdapterInterface;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\ObjectStream;
use React\Filesystem\ObjectStreamSink;
use React\Filesystem\Stream\GenericStreamInterface;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;
use React\Promise\Stream;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

class File implements FileInterface
{
    use GenericOperationTrait;

    /**
     * @var bool
     */
    protected $open = false;
    protected $fileDescriptor;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @param string $filename
     * @param FilesystemInterface $filesystem
     */
    public function __construct($filename, FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->adapter = $filesystem->getAdapter();
        $this->createNameNParentFromFilename($filename);
    }

    /**
     * {@inheritDoc}
     */
    public function exists()
    {
        return $this->stat()->then(function () {
            return new FulfilledPromise();
        }, function () {
            return new RejectedPromise(new \Exception('Not found'));
        });
    }

    /**
     * {@inheritDoc}
     */
    public function size()
    {
        return $this->adapter->stat($this->path)->then(function ($result) {
            return $result['size'];
        });
    }

    /**
     * {@inheritDoc}
     */
    public function time()
    {
        return $this->adapter->stat($this->path)->then(function ($result) {
            return [
                'atime' => $result['atime'],
                'ctime' => $result['ctime'],
                'mtime' => $result['mtime'],
            ];
        });
    }

    /**
     * {@inheritDoc}
     */
    public function rename($toFilename)
    {
        return $this->adapter->rename($this->path, $toFilename)->then(function () use ($toFilename) {
            return $this->filesystem->file($toFilename);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function create($mode = AdapterInterface::CREATION_MODE, $time = null)
    {
        return $this->stat()->then(function () {
            return new RejectedPromise(new \Exception('File exists'));
        }, function () use ($mode, $time) {
            return $this->adapter->touch($this->path, $mode, $time);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function touch($mode = AdapterInterface::CREATION_MODE, $time = null)
    {
        return $this->adapter->touch($this->path, $mode, $time);
    }

    /**
     * {@inheritDoc}
     */
    public function open($flags, $mode = AdapterInterface::CREATION_MODE)
    {
        if ($this->open === true) {
            return new RejectedPromise();
        }

        return $this->adapter->open($this->path, $flags, $mode)->then(function (GenericStreamInterface $stream) {
            $this->open = true;
            $this->fileDescriptor = $stream->getFiledescriptor();
            return $stream;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        if ($this->open === false) {
            return new RejectedPromise();
        }

        return $this->adapter->close($this->fileDescriptor)->then(function () {
            $this->open = false;
            $this->fileDescriptor = null;
            return new FulfilledPromise();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getContents()
    {
        return $this->open('r')->then(function ($stream) {
            return Stream\buffer($stream);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function putContents($contents)
    {
        return $this->open('cw')->then(function (WritableStreamInterface $stream) use ($contents) {
            $stream->write($contents);
            return $this->close();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function remove()
    {
        return $this->adapter->unlink($this->path);
    }

    /**
     * @param NodeInterface $node
     * @return \React\Promise\PromiseInterface
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
        if ($node instanceof FileInterface) {
            return $this->copyToFile($node);
        }

        if ($node instanceof DirectoryInterface) {
            return $this->copyToDirectory($node);
        }

        throw new \UnexpectedValueException('Unsupported node type');
    }

    /**
     * @param FileInterface $node
     * @return ObjectStream
     */
    protected function copyToFile(FileInterface $node)
    {
        $stream = new ObjectStream();

        $this->open('r')->then(function (ReadableStreamInterface $readStream) use ($node, $stream) {
            $readStream->pause();
            return $node->open('ctw')->then(function (WritableStreamInterface $writeStream) use ($readStream, $node, $stream) {
                $deferred = new Deferred();
                $writePromises = [];
                $readStream->on('end', function () use ($deferred, $writeStream, &$writePromises) {
                    \React\Promise\all($writePromises)->then(function ()use ($deferred, $writeStream) {
                        $writeStream->end();
                        $deferred->resolve();
                    });
                });
                $readStream->on('data', function ($data) use ($writeStream, &$writePromises) {
                    $writePromises[] = $writeStream->write($data);
                });
                $readStream->resume();
                return $deferred->promise();
            })->always(function () use ($node) {
                $node->close();
            });
        })->then(function () {
            return $this->close();
        }, function () {
            return $this->close();
        })->then(function () use ($stream, $node) {
            $stream->end($node);
        });

        return $stream;
    }

    /**
     * @param DirectoryInterface $node
     * @return ObjectStream
     */
    protected function copyToDirectory(DirectoryInterface $node)
    {
        return $this->copyToFile($node->getFilesystem()->file($node->getPath() . $this->getName()));
    }
}
