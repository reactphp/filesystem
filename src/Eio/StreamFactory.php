<?php

namespace React\Filesystem\Eio;

use React\Filesystem\EioAdapter;

class StreamFactory
{
    /**
     * @param string $path
     * @param resource $fileDescriptor
     * @param int $flags
     * @param EioAdapter $filesystem
     * @return DuplexStream|ReadableStream|WritableStream
     */
    public static function create($path, $fileDescriptor, $flags, EioAdapter $filesystem)
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
