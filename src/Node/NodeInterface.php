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

    /**
     * @param NodeInterface $node
     * @return \React\Promise\PromiseInterface
     */
    public function copy(NodeInterface $node);
}
