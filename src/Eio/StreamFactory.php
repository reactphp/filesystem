<?php

namespace React\Filesystem\Eio;

use React\Filesystem\EioFilesystem;

class StreamFactory
{
    public static function create($path, $fileDescriptor, $flags, EioFilesystem $filesystem)
    {
        if ($flags == EIO_O_RDONLY) {
            return new ReadableStream($path, $fileDescriptor, $filesystem);
        }

        if ($flags & EIO_O_WRONLY) {
            return new WritableStream($path, $fileDescriptor, $filesystem);
        }

        return new Stream($path, $fileDescriptor, $filesystem);
    }
}
