<?php

namespace React\Filesystem;

use RuntimeException as SplRuntimeException;

class NoAdapterException extends SplRuntimeException
{
    use ArgsExceptionTrait;
}
