<?php

namespace React\Filesystem\Eio;

use RuntimeException as SplRuntimeException;

class RuntimeException extends SplRuntimeException
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
