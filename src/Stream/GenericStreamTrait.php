<?php

namespace React\Filesystem\Stream;

trait GenericStreamTrait
{
    /**
     * {@inheritDoc}
     */
    public function getFiledescriptor()
    {
        return $this->fileDescriptor;
    }
}
