<?php

namespace React\Filesystem;

class PermissionFlagResolver extends FlagResolver implements FlagResolverInterface
{
    const DEFAULT_FLAG = null;

    private $currentScope;

    private $flagMapping = [
        'user' => [
            'w' => 128,
            'x' => 64,
            'r' => 256,
        ],
        'group' => [
            'w' => 16,
            'x' => 8,
            'r' => 32,
        ],
        'universe' => [
            'w' => 2,
            'x' => 1,
            'r' => 4,
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
