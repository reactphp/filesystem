<?php

namespace React\Filesystem;

use React\Filesystem\Node;
use React\Promise\PromiseInterface;

interface AdapterInterface
{
    /**
     * @return PromiseInterface<Node\NodeInterface>
     */
    public function detect(string $path): PromiseInterface;

    public function directory(string $path): Node\DirectoryInterface;

    public function file(string $path): Node\FileInterface;
}
