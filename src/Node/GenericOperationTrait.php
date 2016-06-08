<?php

namespace React\Filesystem\Node;

trait GenericOperationTrait
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var NodeInterface|null
     */
    protected $parent;

    /**
     * @param string $path
     */
    protected function createNameNParentFromFilename($path)
    {
        $this->path = $path;

        $path = rtrim($path, NodeInterface::DS);

        $pathBits = explode(NodeInterface::DS, $path);
        $this->name = array_pop($pathBits);

        if (count($pathBits) > 0) {
            $this->parent = $this->filesystem->dir(implode(NodeInterface::DS, $pathBits));
            $this->path = $this->parent->getPath() . $this->getName();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->getPath();
    }

    /**
     * {@inheritDoc}
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * {@inheritDoc}
     */
    public function stat()
    {
        return $this->adapter->stat($this->getPath());
    }

    /**
     * {@inheritDoc}
     */
    public function chmod($mode)
    {
        return $this->adapter->chmod($this->getPath(), $mode);
    }

    /**
     * {@inheritDoc}
     */
    public function chown($uid = -1, $gid = -1)
    {
        return $this->adapter->chown($this->getPath(), $uid, $gid);
    }
}
