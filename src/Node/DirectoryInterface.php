<?php

namespace React\Filesystem\Node;

use React\Filesystem\AdapterInterface;
use React\Promise\PromiseInterface;

interface DirectoryInterface extends NodeInterface
{
    /**
     * Create the directory, fails when directory already exists or when parent doesn't exist.
     *
     * @return PromiseInterface
     */
    public function create($mode = AdapterInterface::CREATION_MODE);

    /**
     * Create the directory, creating any parent that doesn't exist.
     *
     * @return PromiseInterface
     */
    public function createRecursive($mode = AdapterInterface::CREATION_MODE);

    /**
     * Remove the directory, fails when it has contents.
     *
     * @return PromiseInterface
     */
    public function remove();

    /**
     * Rename the directory and return the new directory through a promise
     *
     * @param string $toDirectoryName
     * @return PromiseInterface<DirectoryInterface>
     */
    public function rename($toDirectoryName);

    /**
     * List contents of the directory.
     *
     * @return PromiseInterface
     */
    public function ls();

    /**
     * List contents of the directory and any child directories recursively.
     *
     * @return PromiseInterface
     */
    public function lsRecursive();

    /**
     * Change mode recursively.
     *
     * @param int $mode
     * @return PromiseInterface
     */
    public function chmodRecursive($mode);

    /**
     * Change owner recursively.
     *
     * @return PromiseInterface
     */
    public function chownRecursive();

    /**
     * Remove the directory and all its contents recursively.
     *
     * @return PromiseInterface
     */
    public function removeRecursive();

    /**
     * @param DirectoryInterface $directory
     * @return PromiseInterface
     */
    //public function rsync(DirectoryInterface $directory);
}
