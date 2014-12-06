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
     * @return \React\Promise\PromiseInterface
     */
    public function open($flags);

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
}
