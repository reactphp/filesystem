<?php

namespace React\Filesystem\Node;

interface LinkInterface extends NodeInterface
{
    /**
     * Return the node this link is pointing at.
     *
     * @return NodeInterface
     */
    public function getDestination();
}
