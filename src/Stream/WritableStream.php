<?php

namespace React\Filesystem\Stream;

use Evenement\EventEmitter;
use React\Filesystem\AdapterInterface;
use React\Stream\WritableStreamInterface;

class WritableStream extends EventEmitter implements GenericStreamInterface, WritableStreamInterface
{
    use WritableStreamTrait;
    use GenericStreamTrait;

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
}
