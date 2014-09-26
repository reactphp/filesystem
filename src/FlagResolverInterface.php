<?php

namespace React\Filesystem;

interface FlagResolverInterface
{
    public function defaultFlags();

    public function flagMapping();
}
