<?php

namespace React\Filesystem\Stream;

interface GenericStreamInterface
{
    /**
     * @return resource
     */
    public function getFiledescriptor();
}
