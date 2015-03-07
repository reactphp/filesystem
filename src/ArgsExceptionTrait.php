<?php

namespace React\Filesystem;

trait ArgsExceptionTrait
{
    protected $args = [];

    public function setArgs(array $args = [])
    {
        $this->args = $args;
    }

    public function getArgs()
    {
        return $this->args;
    }
}
