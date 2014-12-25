<?php

namespace React\Filesystem\Node;

use React\Filesystem\AdapterInterface;
use React\Filesystem\Stream\GenericStreamInterface;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;
use React\Stream\BufferedSink;

class File implements FileInterface, GenericOperationInterface
{

    use GenericOperationTrait;

    protected $open = false;
    protected $fileDescriptor;

    /**
     * @param string $filename
     * @param AdapterInterface $filesystem
     */
    public function __construct($filename, AdapterInterface $filesystem)
    {
        $this->filename = $filename;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return $this->filename;
    }

    /**
     * {@inheritDoc}
     */
    public function exists()
    {
        return $this->stat()->then(function () {
            return new FulfilledPromise();
        }, function () {
            return new RejectedPromise();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function size()
    {
        return $this->filesystem->stat($this->filename)->then(function ($result) {
            return $result['size'];
        });
    }

    /**
     * {@inheritDoc}
     */
    public function time()
    {
        return $this->filesystem->stat($this->filename)->then(function ($result) {
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
        return $this->filesystem->rename($this->filename, $toFilename);
    }

    /**
     * {@inheritDoc}
     */
    public function create()
    {
        return $this->stat()->then(function () {
            return new RejectedPromise(new \Exception('File exists'));
        }, function () {
            return $this->filesystem->touch($this->filename);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function open($flags)
    {
        if ($this->open === true) {
            return new RejectedPromise();
        }

        return $this->filesystem->open($this->filename, $flags)->then(function (GenericStreamInterface $stream) {
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

        return $this->filesystem->close($this->fileDescriptor)->then(function () {
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
            return BufferedSink::createPromise($stream);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function remove()
    {
        return $this->filesystem->unlink($this->filename);
    }
}
