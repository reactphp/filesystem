<?php

namespace React\Filesystem\Eio;

use UnexpectedValueException as SplUnexpectedValueException;

class UnexpectedValueException extends SplUnexpectedValueException
{
    use ArgsExceptionTrait;
} 