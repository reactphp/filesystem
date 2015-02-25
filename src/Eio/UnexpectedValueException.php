<?php

namespace React\Filesystem\Eio;

use React\Filesystem\ArgsExceptionTrait;
use UnexpectedValueException as SplUnexpectedValueException;

class UnexpectedValueException extends SplUnexpectedValueException
{
    use ArgsExceptionTrait;
}
