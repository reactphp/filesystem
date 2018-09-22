<?php

namespace React\Filesystem\Stream;

use Evenement\EventEmitter;
use React\Filesystem\AdapterInterface;
use React\Stream\DuplexStreamInterface;
use React\Filesystem\ThrottledQueuedInvoker;

class DuplexStream extends EventEmitter implements DuplexStreamInterface, GenericStreamInterface
{
    use ReadableStreamTrait;
    use WritableStreamTrait;
    use GenericStreamTrait;

    /**
     * @param string $path
     * @param mixed $fileDescriptor
     * @param AdapterInterface $filesystem
     */
    public function __construct($path, $fileDescriptor, AdapterInterface $filesystem)
    {
        $this->path = $path;
        $this->setFilesystem($filesystem);
        $this->fileDescriptor = $fileDescriptor;

        $this->callInvoker = new ThrottledQueuedInvoker($filesystem);
    }

    protected function readChunk()
    {
        if ($this->pause) {
            return;
        }

        $this->resolveSize()->then(function () {
            $this->performRead($this->calculateChunkSize());
        });
    }

    protected function resolveSize()
    {
        if ($this->readCursor < $this->size) {
            return \React\Promise\resolve();
        }

        return $this->callInvoker->invokeCall('eio_stat', [$this->path])->then(function ($stat) {
            $this->size = $stat['size'];
            return \React\Promise\resolve();
        });
    }
}
