<?php

namespace React\Filesystem\Eio;

use RuntimeException as SplRuntimeException;

class RuntimeException extends SplRuntimeException
{
    use ArgsExceptionTrait;
} 