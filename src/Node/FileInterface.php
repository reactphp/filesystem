<?php

namespace React\Filesystem\Node;

use React\Filesystem\AdapterInterface;
use React\Promise\PromiseInterface;

interface FileInterface extends NodeInterface
{
    /**
     * Returns true when the file exists
     *
     * @return PromiseInterface
     */
    public function exists();

    /**
     * Remove the file
     *
     * @return PromiseInterface
     */
    public function remove();

    /**
     * Open the file and return a promise resolve into a stream that can be read from or written to.
     *
     * @param $flags
     * @param string $mode
     * @return PromiseInterface<GenericStreamInterface>
     */
    public function open($flags, $mode = AdapterInterface::CREATION_MODE);

    /**
     * Return the change time, access time, and modification time
     *
     * @return PromiseInterface
     */
    public function time();

    /**
     * Rename the file and return the new file through a promise
     *
     * @param string $toFilename
     * @return PromiseInterface<FileInterface>
     */
    public function rename($toFilename);

    /**
     * Return the size of the file.
     *
     * @return PromiseInterface
     */
    public function size();

    /**
     * Create the file
     *
     * @param string $mode
     * @param null $time
     * @return PromiseInterface
     *
     * @throws \Exception When the file already exists
     */
    public function create($mode = AdapterInterface::CREATION_MODE, $time = null);

    /**
     * Touch the file, modifying it's mtime when it exists,
     * or creating the file when it doesn't it exists.
     *
     * @param string $mode
     * @param null $time
     * @return PromiseInterface
     */
    public function touch($mode = AdapterInterface::CREATION_MODE, $time = null);

    /**
     * Open the file and read all its contents returning those through a promise.
     *
     * @return PromiseInterface<string>
     */
    public function getContents();

    /**
     * Write the given contents to the file, overwriting any existing contents or creating the file.
     *
     * @param string $contents
     * @return PromiseInterface
     */
    public function putContents($contents);
}
