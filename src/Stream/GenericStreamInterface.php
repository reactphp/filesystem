<?php

namespace React\Filesystem\Stream;

interface GenericStreamInterface
{
    /**
     * @return mixed
     */
    public function getFiledescriptor();
}
