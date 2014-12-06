<?php

namespace React\Filesystem\Node;

interface GenericOperationInterface
{
    /**
     * @return \React\Filesystem\AdapterInterface
     */
    public function getFilesystem();

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function stat();

    /**
     * @param int $mode
     * @return \React\Promise\PromiseInterface
     */
    public function chmod($mode);

    /**
     * @param int $uid
     * @param int $gid
     * @return \React\Promise\PromiseInterface
     */
    public function chown($uid = -1, $gid = -1);
}
