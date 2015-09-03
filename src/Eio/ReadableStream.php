<?php

namespace React\Filesystem\Eio;

use Evenement\EventEmitter;
use React\Filesystem\EioAdapter;
use React\Filesystem\Stream\GenericStreamInterface;
use React\Filesystem\Stream\GenericStreamTrait;
use React\Stream\ReadableStreamInterface;

class ReadableStream extends EventEmitter implements GenericStreamInterface, ReadableStreamInterface
{
    use ReadableStreamTrait;
    use GenericStreamTrait;

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

        $this->resume();
    }
}
