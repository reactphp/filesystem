<?php

namespace React\Filesystem\Node;

use React\Filesystem\AdapterInterface;

interface DirectoryInterface
{
    /**
     * @return \React\Promise\PromiseInterface
     */
    public function create($mode = AdapterInterface::CREATION_MODE);

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function remove();

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function ls();

    /**
     * @param int $mode
     * @return \React\Promise\PromiseInterface
     */
    public function chmodRecursive($mode);

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function chownRecursive();

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function removeRecursive();

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function lsRecursive();

    /**
     * @param DirectoryInterface $directory
     * @return \React\Promise\PromiseInterface
     */
    //public function rsync(DirectoryInterface $directory);
}
