<?php

namespace React\Filesystem\Eio;

use React\Filesystem\Stream\GenericStreamInterface;
use React\Filesystem\Stream\GenericStreamTrait;

class DuplexStream implements GenericStreamInterface
{
    use GenericStreamTrait;
}
