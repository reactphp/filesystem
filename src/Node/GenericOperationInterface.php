<?php

namespace React\Filesystem\Node;

interface GenericOperationInterface
{

    public function getFilesystem();

    public function stat();

    public function chmod($mode);

    public function chown($uid = -1, $gid = -1);
}
