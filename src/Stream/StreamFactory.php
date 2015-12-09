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
        if (strpos($flags, 'r') !== false) {
            return new ReadableStream($path, $fileDescriptor, $filesystem);
        }

        if (strpos($flags, 'w') !== false) {
            return new WritableStream($path, $fileDescriptor, $filesystem);
        }

        return new DuplexStream($path, $fileDescriptor, $filesystem);
    }
}
