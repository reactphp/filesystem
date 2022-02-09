<?php

namespace React\Filesystem\Node;

use React\Promise\PromiseInterface;
use Rx\Observable;

interface NotExistInterface extends NodeInterface
{
    /**
     * @return PromiseInterface<DirectoryInterface>
     */
    public function createDirectory(): PromiseInterface;

    /**
     * @return PromiseInterface<FileInterface>
     */
    public function createFile(): PromiseInterface;
}
