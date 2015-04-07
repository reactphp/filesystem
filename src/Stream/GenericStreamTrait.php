<?php

namespace React\Filesystem\Stream;

use React\Filesystem\AdapterInterface;
use React\Filesystem\InstantInvoker;

trait GenericStreamTrait
{
    protected $path;
    protected $filesystem;
    protected $fileDescriptor;
    protected $closed = false;
    protected $callInvoker;

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

        $this->callInvoker = new InstantInvoker($filesystem);
    }

    /**
     * {@inheritDoc}
     */
    public function getFiledescriptor()
    {
        return $this->fileDescriptor;
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
}
