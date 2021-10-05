<?php

namespace React\Filesystem\Node;

use React\Promise\PromiseInterface;

interface FileInterface extends NodeInterface
{
    /**
     * Open the file and read all its contents returning those through a promise.
     *
     * @return PromiseInterface<string>
     */
    public function getContents(int $offset = 0 , ?int $maxlen = null);

    /**
     * Write the given contents to the file, overwriting any existing contents or creating the file.
     *
     * @param string $contents
     * @return PromiseInterface<int>
     */
    public function putContents(string $contents, int $flags = 0);
}
