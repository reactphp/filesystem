<?php

namespace React\Filesystem\Node;

use React\Filesystem\AdapterInterface;
use React\Promise\PromiseInterface;

interface GenericOperationInterface
{
    /**
     * Return the filesystem associated with this node
     *
     * @return AdapterInterface
     */
    public function getFilesystem();

    /**
     * Stat the node, returning information such as the file, c/m/a-time, mode, g/u-id, and more.
     *
     * @return PromiseInterface
     */
    public function stat();

    /**
     * Change the node mode
     *
     * @param int $mode
     * @return PromiseInterface
     */
    public function chmod($mode);

    /**
     * Change the owner of the node
     *
     * @param int $uid
     * @param int $gid
     * @return PromiseInterface
     */
    public function chown($uid = -1, $gid = -1);
}
