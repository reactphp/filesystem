<?php

namespace React\Filesystem\Stream;

use React\Filesystem\AdapterInterface;

class StreamFactory
{
    /**
     * @param string $path
     * @param resource $fileDescriptor
     * @param int $flags
     * @param AdapterInterface $filesystem
     * @return DuplexStream|ReadableStream|WritableStream
     */
    public static function create($path, $fileDescriptor, $flags, AdapterInterface $filesystem)
    {
        if ($flags == EIO_O_RDONLY) {
            return new ReadableStream($path, $fileDescriptor, $filesystem);
        }

        if ($flags & EIO_O_WRONLY) {
            return new WritableStream($path, $fileDescriptor, $filesystem);
        }

        return new DuplexStream($path, $fileDescriptor, $filesystem);
    }
}
