<?php

namespace React\Filesystem\Node;

interface LinkInterface extends NodeInterface
{
    /**
     * @return NodeInterface
     */
    public function getDestination();
}
