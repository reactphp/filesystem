<?php

namespace React\Filesystem\Node;

use React\Filesystem\AdapterInterface;

interface FileInterface
{
    public function __construct($filename, AdapterInterface $filesystem);

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function exists();

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function remove();

    /**
     * @param $flags
     * @param string $mode
     * @param null $time
     * @return mixed
     */
    public function open($flags, $mode = AdapterInterface::CREATION_MODE);

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function time();

    /**
     * @param string $toFilename
     * @return \React\Promise\PromiseInterface
     */
    public function rename($toFilename);

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function size();

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function create($mode = AdapterInterface::CREATION_MODE, $time = null);

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function touch($mode = AdapterInterface::CREATION_MODE, $time = null);
}
