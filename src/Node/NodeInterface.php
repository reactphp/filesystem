<?php

namespace React\Filesystem\Node;

use React\Filesystem\ObjectStream;
use React\Promise\PromiseInterface;

interface NodeInterface extends GenericOperationInterface
{
    const DS = DIRECTORY_SEPARATOR;

    /**
     * Alias for getPath()
     *
     * @return string
     */
    public function __toString();

    /**
     * Return the parent node, or null if this is already the top node.
     *
     * @return NodeInterface|null
     */
    public function getParent();

    /**
     * Return the full path, for example: /path/to/file.ext
     *
     * @return string
     */
    public function getPath();

    /**
     * Return the node name, for example: file.ext
     *
     * @return string
     */
    public function getName();

    /**
     * Copy this node to the given node, returning the all results when done.
     *
     * @param NodeInterface $node
     * @return PromiseInterface
     */
    public function copy(NodeInterface $node);

    /**
     * Copy this node to the given node streaming. The returned object is a stream,
     * that emits events for each copy node during this operation.
     *
     * @param NodeInterface $node
     * @return ObjectStream
     */
    public function copyStreaming(NodeInterface $node);
}
