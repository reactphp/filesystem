<?php

namespace React\Filesystem\Filesystem;

use React\Filesystem\FlagResolver;

class EioOpenFlagResolver extends FlagResolver
{
    protected $flagMapping = [
        '+' => EIO_O_RDWR,
        'a' => EIO_O_APPEND,
        'c' => EIO_O_CREAT,
        'e' => EIO_O_EXCL,
        'f' => EIO_O_FSYNC,
        'n' => EIO_O_NONBLOCK,
        'r' => EIO_O_RDONLY,
        't' => EIO_O_TRUNC,
        'w' => EIO_O_WRONLY,
    ];
}
