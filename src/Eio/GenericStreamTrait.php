<?php

namespace React\Filesystem\Eio;

trait GenericStreamTrait
{

    public function getFiledescriptor()
    {
        return $this->fileDescriptor;
    }
}
