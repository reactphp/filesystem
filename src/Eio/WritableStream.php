<?php

namespace React\Filesystem\Eio;

use Evenement\EventEmitter;
use React\Filesystem\EioAdapter;
use React\Filesystem\Stream\GenericStreamInterface;
use React\Filesystem\Stream\GenericStreamTrait;
use React\Filesystem\Stream\WritableStreamInterface;

class WritableStream extends EventEmitter implements GenericStreamInterface, WritableStreamInterface
{
    use GenericStreamTrait;

    protected $path;
    protected $filesystem;
    protected $fileDescriptor;
    protected $cursor = 0;
    protected $closed = false;

    /**
     * @param string $path
     * @param resource $fileDescriptor
     * @param EioAdapter $filesystem
     */
    public function __construct($path, $fileDescriptor, EioAdapter $filesystem)
    {
        $this->path = $path;
        $this->filesystem = $filesystem;
        $this->fileDescriptor = $fileDescriptor;
    }

    /**
     * {@inheritDoc}
     */
    public function write($data)
    {
        $length = strlen($data);
        $offset = $this->cursor;
        $this->cursor += $length;

        return $this->filesystem->write($this->fileDescriptor, $data, $length, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function end($data = null)
    {
        if (null !== $data) {
            $this->write($data);
        }

        $this->close();
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;
        $this->emit('end', [$this]);

        $this->filesystem->close($this->fileDescriptor)->then(function () {
            $this->emit('close', [$this]);
            $this->removeAllListeners();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function isWritable()
    {
        return !$this->closed;
    }
}
