<?php

namespace React\Filesystem\Node;

use Exception;
use React\Filesystem\AdapterInterface;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\ObjectStream;
use React\Filesystem\ObjectStreamSink;
use React\Filesystem\Stream\GenericStreamInterface;
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
            return null;
        }, function () {
            throw new Exception('Not found');
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
            throw new \Exception('File exists already');
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
            return \React\Promise\reject(new Exception('File is already open'));
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
            return \React\Promise\reject(new Exception('File is already closed'));
        }

        return $this->adapter->close($this->fileDescriptor)->then(function () {
            $this->open = false;
            $this->fileDescriptor = null;
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

        $this->open('r')->then(function (ReadableStreamInterface $readStream) use ($node) {
            $readStream->pause();

            return \React\Promise\all([
                'read' => $readStream,
                'write' => $node->open('ctw'),
            ]);
        })->then(function (array $streams) use ($stream, $node) {
            $streams['read']->pipe($streams['write']);
            $streams['read']->on('close', function () use ($streams, $stream, $node) {
                $streams['write']->close();
                $stream->end($node);
            });
            $streams['read']->resume();
        })->done();

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
