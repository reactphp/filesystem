<?php

namespace React\Filesystem\Uv;

use React\Filesystem\FlagResolver;
use React\Filesystem\FlagResolverInterface;

class PermissionFlagResolver extends FlagResolver implements FlagResolverInterface
{
    const DEFAULT_FLAG = null;

    private $currentScope;

    private $flagMapping = [
        'user' => [
            'w' => \UV::S_IWOTH,
            'x' => \UV::S_IXOTH,
            'r' => \UV::S_IROTH,
        ],
        'group' => [
            'w' => \UV::S_IWGRP,
            'x' => \UV::S_IXGRP,
            'r' => \UV::S_IRGRP,
        ],
        'universe' => [
            'w' => \UV::S_IWUSR,
            'x' => \UV::S_IXUSR,
            'r' => \UV::S_IRUSR,
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
        if (\is_int($flag)) {
            return $flag;
        }

        $resultFlags = 0;
        $start = 0;

        foreach ([
            'universe',
            'group',
            'user',
        ] as $scope) {
            $this->currentScope = $scope;
            $start -= 3;
            $chunk = \substr($flag, $start, 3);
            $resultFlags |= parent::resolve($chunk, $flags, $mapping);
        }

        return $resultFlags;
    }
}