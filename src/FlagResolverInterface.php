<?php

namespace React\Filesystem;

interface FlagResolverInterface
{
    /**
     * @return int
     */
    public function defaultFlags();

    /**
     * @return array
     */
    public function flagMapping();
}
