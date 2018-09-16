<?php

namespace React\Filesystem\Eio;

use React\Filesystem\FlagResolver;
use React\Filesystem\FlagResolverInterface;

class OpenFlagResolver extends FlagResolver implements FlagResolverInterface
{
    const DEFAULT_FLAG = null;

    private $flagMapping = [
        '+' => EIO_O_RDWR,
        'a' => EIO_O_APPEND,
        'c' => EIO_O_CREAT,
        'e' => EIO_O_EXCL,
        'n' => EIO_O_NONBLOCK,
        'r' => EIO_O_RDONLY,
        't' => EIO_O_TRUNC,
        'w' => EIO_O_WRONLY,
    ];

    /**
     * {@inheritDoc}
     */
    public function defaultFlags()
    {
        return static::DEFAULT_FLAG;
    }

    /**
     * {@inheritDoc}
     */
    public function flagMapping()
    {
        return $this->flagMapping;
    }
}
