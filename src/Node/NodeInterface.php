<?php

namespace React\Filesystem\Node;

interface NodeInterface
{
    const DS = DIRECTORY_SEPARATOR;

    /**
     * @return string
     */
    public function __toString();

    /**
     * @return NodeInterface|null
     */
    public function getParent();

    /**
     * @return string
     */
    public function getPath();

    /**
     * @return string
     */
    public function getName();

    /**
     * @param NodeInterface $node
     * @return \React\Promise\PromiseInterface
     */
    //public function copy(NodeInterface $node);
}
