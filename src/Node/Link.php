<?php

namespace React\Filesystem\Node;

use React\Filesystem\FilesystemInterface;

class Link implements LinkInterface
{
    use GenericOperationTrait;

    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * Link constructor.
     * @param string $path
     * @param NodeInterface $node
     * @param FilesystemInterface $filesystem
     */
    public function __construct($path, NodeInterface $node, FilesystemInterface $filesystem)
    {
        $this->node = $node;
        $this->filesystem = $filesystem;
        $this->adapter = $filesystem->getAdapter();
        $this->createNameNParentFromFilename($path);
    }

    /**
     * @inheritDoc
     */
    public function getDestination()
    {
        return $this->node;
    }

    /**
     * @inheritDoc
     */
    public function copy(NodeInterface $node)
    {
        return $this->node->copy($node);
    }

    /**
     * @inheritDoc
     */
    public function copyStreaming(NodeInterface $node)
    {
        return $this->node->copyStreaming($node);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->node->$name(...$arguments);
    }
}
