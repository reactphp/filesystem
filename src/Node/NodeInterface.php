<?php

namespace React\Filesystem\Node;

interface NodeInterface
{
    /**
     * @return string
     */
    public function __toString();

    /**
     * @return string
     */
    public function getPath();
}
