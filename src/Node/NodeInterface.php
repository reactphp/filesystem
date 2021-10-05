<?php

namespace React\Filesystem\Node;

use React\Filesystem\Stat;
use React\Promise\PromiseInterface;

interface NodeInterface
{
    const DS = DIRECTORY_SEPARATOR;

    /**
     * Return the full path, for example: /path/to/
     *
     * @return string
     */
    public function path();

    /**
     * Return the node name, for example: file.ext
     *
     * @return string
     */
    public function name();

    /**
     * Return the node name, for example: file.ext
     *
     * @return PromiseInterface<?Stat>
     */
    public function stat(): PromiseInterface;

    /**
     * Remove the node from the filesystem, errors on non-empty directories
     *
     * @return PromiseInterface<bool>
     */
    public function unlink(): PromiseInterface;
}
