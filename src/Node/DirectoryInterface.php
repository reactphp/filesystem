<?php

namespace React\Filesystem\Node;

interface DirectoryInterface
{
    /**
     * @return \React\Promise\PromiseInterface
     */
    public function create();

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
}
