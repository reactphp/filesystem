<?php

namespace React\Filesystem\Eio;

use React\Filesystem\ArgsExceptionTrait;
use RuntimeException as SplRuntimeException;

class RuntimeException extends SplRuntimeException
{
    use ArgsExceptionTrait;
}
