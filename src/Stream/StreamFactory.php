<?php

namespace React\Filesystem\Stream;

use InvalidArgumentException;
use React\Filesystem\AdapterInterface;

class StreamFactory
{
    /**
     * @param string $path
     * @param mixed $fileDescriptor
     * @param int $flags
     * @param AdapterInterface $filesystem
     * @return DuplexStream|ReadableStream|WritableStream
     * @throws InvalidArgumentException
     */
    public static function create($path, $fileDescriptor, $flags, AdapterInterface $filesystem)
    {
        if (strpos($flags, '+') !== false) {
            return new DuplexStream($path, $fileDescriptor, $filesystem);
        }

        if (strpos($flags, 'w') !== false || strpos($flags, 'a') !== false) {
            return new WritableStream($path, $fileDescriptor, $filesystem);
        }

        if (strpos($flags, 'r') !== false) {
            return new ReadableStream($path, $fileDescriptor, $filesystem);
        }

        throw new InvalidArgumentException('Unsupported flags for stream');
    }
}
