<?php

namespace React\Filesystem\Eio;

use React\Filesystem\FlagResolver;
use React\Filesystem\FlagResolverInterface;

class PermissionFlagResolver extends FlagResolver implements FlagResolverInterface
{
    const DEFAULT_FLAG = null;

    private $currentScope;

    private $flagMapping = [
        'user' => [
            'w' => EIO_S_IWUSR,
            'x' => EIO_S_IXUSR,
            'r' => EIO_S_IRUSR,
        ],
        'group' => [
            'w' => EIO_S_IWGRP,
            'x' => EIO_S_IXGRP,
            'r' => EIO_S_IRGRP,
        ],
        'universe' => [
            'w' => EIO_S_IWOTH,
            'x' => EIO_S_IXOTH,
            'r' => EIO_S_IROTH,
        ],
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
        return $this->flagMapping[$this->currentScope];
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($flag, $flags = null, $mapping = null)
    {
        $resultFlags = 0;
        $start = 0;

        foreach ([
            'universe',
            'group',
            'user',
        ] as $scope) {
            $this->currentScope = $scope;
            $start -= 3;
            $chunk = substr($flag, $start, 3);
            $resultFlags |= parent::resolve($chunk, $flags, $mapping);
        }

        return $resultFlags;
    }
}
