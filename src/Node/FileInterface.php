<?php

namespace React\Filesystem\Node;

use React\Filesystem\AdapterInterface;
use React\Promise\PromiseInterface;

interface FileInterface extends NodeInterface
{
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
     * @param string $mode
     * @param null $time
     * @return \React\Promise\PromiseInterface
     */
    public function create($mode = AdapterInterface::CREATION_MODE, $time = null);

    /**
     * @param string $mode
     * @param null $time
     * @return \React\Promise\PromiseInterface
     */
    public function touch($mode = AdapterInterface::CREATION_MODE, $time = null);

    /**
     * @return PromiseInterface
     */
    public function getContents();

    /**
     * @param string $contents
     * @return PromiseInterface
     */
    public function putContents($contents);
}
