<?php

namespace React\Filesystem\Stream;

interface DuplexStreamInterface extends ReadableStreamInterface, WritableStreamInterface
{
    /**
     * @todo decide about this before first tag
     * Not sure whether having these interfaces like this or not is a good idea.
     * The thought behind it is to differentiate from normal streams and that if
     * we ever need to add extra things in here not everyone has to switch interfaces over.
     */
}
