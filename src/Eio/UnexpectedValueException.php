<?php

namespace React\Filesystem\Eio;

use UnexpectedValueException as SplUnexpectedValueException;

class UnexpectedValueException extends SplUnexpectedValueException
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
