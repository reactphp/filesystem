<?php

namespace React\Filesystem\Node;

interface DirectoryInterface
{
    public function create();

    public function remove();

    public function ls();

    public function chmodRecursive($mode);

    public function chownRecursive();

    public function removeRecursive();

    public function lsRecursive();
}
