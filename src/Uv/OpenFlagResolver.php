<?php

namespace React\Filesystem\Uv;

use React\Filesystem\FlagResolver;
use React\Filesystem\FlagResolverInterface;

class OpenFlagResolver extends FlagResolver implements FlagResolverInterface
{
    const DEFAULT_FLAG = null;

    private $flagMapping = [
        '+' => \UV::O_RDWR,
        'a' => \UV::O_APPEND,
        'c' => \UV::O_CREAT,
        'e' => \UV::O_EXCL,
        'r' => \UV::O_RDONLY,
        't' => \UV::O_TRUNC,
        'w' => \UV::O_WRONLY,
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
