<?php

namespace React\Filesystem\Eio;

use React\Filesystem\AdapterInterface;

trait WritableStreamTrait
{
    /**
     * @var AdapterInterface
     */
    protected $filesystem;

    /**
     * @var resource
     */
    protected $fileDescriptor;
    protected $writeCursor = 0;
    protected $closed = false;

    /**
     * {@inheritDoc}
     */
    public function write($data)
    {
        $length = strlen($data);
        $offset = $this->writeCursor;
        $this->writeCursor += $length;

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
    public function isWritable()
    {
        return !$this->closed;
    }

    abstract function close();
}
