<?php

namespace React\Filesystem\Stream;

use React\Filesystem\AdapterInterface;

trait GenericStreamTrait
{
    protected $path;
    protected $filesystem;
    protected $fileDescriptor;
    protected $closed = false;

    /**
     * @param string $path
     * @param resource $fileDescriptor
     * @param AdapterInterface $filesystem
     */
    public function __construct($path, $fileDescriptor, AdapterInterface $filesystem)
    {
        $this->path = $path;
        $this->filesystem = $filesystem;
        $this->fileDescriptor = $fileDescriptor;
    }

    /**
     * @return AdapterInterface
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * @param AdapterInterface $filesystem
     */
    public function setFilesystem($filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritDoc}
     */
    public function getFiledescriptor()
    {
        return $this->fileDescriptor;
    }

    /**
     * @return boolean
     */
    public function isClosed()
    {
        return $this->closed;
    }

    /**
     * @param boolean $closed
     */
    public function setClosed($closed)
    {
        $this->closed = $closed;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
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

        $this->filesystem->close($this->fileDescriptor)->then(function () {
            $this->emit('close', [$this]);
            $this->removeAllListeners();
        });
    }
}
