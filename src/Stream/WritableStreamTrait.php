<?php

namespace React\Filesystem\Stream;

trait WritableStreamTrait
{
    protected $writeCursor = 0;

    /**
     * {@inheritDoc}
     */
    public function write($data)
    {
        $length = strlen($data);
        $offset = $this->writeCursor;
        $this->writeCursor += $length;

        return $this->getFilesystem()->write($this->getFileDescriptor(), $data, $length, $offset);
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
        return !$this->isClosed();
    }

    abstract function close();
}
