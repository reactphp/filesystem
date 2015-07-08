<?php

namespace React\Tests\Filesystem;

use React\Filesystem\Node\NodeInterface;

class UnknownNodeType implements NodeInterface
{
    /**
     * @return NodeInterface|null
     */
    public function getParent()
    {
        // TODO: Implement getParent() method.
    }

    /**
     * @return string
     */
    public function getPath()
    {
        // TODO: Implement getPath() method.
    }

    /**
     * @return string
     */
    public function getName()
    {
        // TODO: Implement getName() method.
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getPath();
    }
}
