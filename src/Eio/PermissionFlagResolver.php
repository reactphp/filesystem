<?php

namespace React\Filesystem\Eio;

use React\Filesystem\FlagResolver;
use React\Filesystem\FlagResolverInterface;

class PermissionFlagResolver extends FlagResolver implements FlagResolverInterface
{
    const DEFAULT_FLAG = 0;

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
        'special' => [],
    ];

    public function defaultFlags()
    {
        return static::DEFAULT_FLAG;
    }

    public function flagMapping()
    {
        return $this->flagMapping[$this->currentScope];
    }

    public function resolve($flag)
    {
        $flags = 0;
        $start = 0;

        foreach ([
            'universe',
            'group',
            'user',
        ] as $scope) {
            $this->currentScope = $scope;
            $start -= 3;
            $chunk = substr($flag, $start, 3);
            $flags |= parent::resolve($chunk);
        }

        if (strlen($flag) > 9) {
            $this->currentScope = 'special';
            $chunk = substr($flag, 0, (strlen($flag) - 9));
            $flags |= parent::resolve($chunk);
        }

        return $flags;
    }
}
