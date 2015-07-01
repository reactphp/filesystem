<?php

namespace React\Filesystem\Node;

trait GenericOperationTrait
{
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
        return $this->filesystem->stat($this->getPath());
    }

    /**
     * {@inheritDoc}
     */
    public function chmod($mode)
    {
        return $this->filesystem->chmod($this->getPath(), $mode);
    }

    /**
     * {@inheritDoc}
     */
    public function chown($uid = -1, $gid = -1)
    {
        return $this->filesystem->chown($this->getPath(), $uid, $gid);
    }
}
