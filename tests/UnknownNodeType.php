<?php

namespace React\Tests\Filesystem;

use React\Filesystem\Node\NodeInterface;
use React\Filesystem\Node\ObjectStream;

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

    /**
     * @return \React\Filesystem\AdapterInterface
     */
    public function getFilesystem()
    {
        // TODO: Implement getFilesystem() method.
    }

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function stat()
    {
        // TODO: Implement stat() method.
    }

    /**
     * @param int $mode
     * @return \React\Promise\PromiseInterface
     */
    public function chmod($mode)
    {
        // TODO: Implement chmod() method.
    }

    /**
     * @param int $uid
     * @param int $gid
     * @return \React\Promise\PromiseInterface
     */
    public function chown($uid = -1, $gid = -1)
    {
        // TODO: Implement chown() method.
    }

    /**
     * @param NodeInterface $node
     * @return \React\Promise\PromiseInterface
     */
    public function copy(NodeInterface $node)
    {
        // TODO: Implement copy() method.
    }

    /**
     * @param NodeInterface $node
     * @return ObjectStream
     */
    public function copyStreaming(NodeInterface $node)
    {
        // TODO: Implement copyStreaming() method.
    }
}
