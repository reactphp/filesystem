<?php

namespace React\Filesystem\Stream;

trait GenericStreamTrait
{

    public function getFiledescriptor()
    {
        return $this->fileDescriptor;
    }
}
