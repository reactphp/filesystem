<?php

namespace React\Filesystem\Eio;

use Evenement\EventEmitter;
use React\Filesystem\EioFilesystem;
use React\Stream\WritableStreamInterface;

class WritableStream extends EventEmitter implements WritableStreamInterface
{
    protected $path;
    protected $filesystem;
    protected $fileDescriptor;
    protected $cursor = 0;
    protected $closed = false;

    public function __construct($path, $fileDescriptor, EioFilesystem $filesystem)
    {
        $this->path = $path;
        $this->filesystem = $filesystem;
        $this->fileDescriptor = $fileDescriptor;
    }

    public function write($data)
    {
        $length = strlen($data);
        $offset = $this->cursor;
        $this->cursor += $length;

        return $this->filesystem->write($this->fileDescriptor, $data, $length, $offset);
    }

    public function end($data = null)
    {
        if (null !== $data) {
            $this->write($data);
        }

        $this->close();
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;
        $this->emit('end', array($this));
        $this->emit('close', array($this));
        $this->removeAllListeners();
    }

    public function isWritable()
    {
        return !$this->closed;
    }
}
