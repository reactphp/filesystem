<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;

interface AdapterInterface
{
    const CREATION_MODE = 'rw-rw-rw-';

    /**
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop);

    /**
     * @return LoopInterface
     */
    public function getLoop();

    /**
     * @param string $function
     * @param array $args
     * @param int $errorResultCode
     * @return \React\Promise\Promise
     */
    public function callFilesystem($function, $args, $errorResultCode = -1);

    /**
     * @param string $path
     * @param $mode
     * @return \React\Promise\PromiseInterface
     */
    public function mkdir($path, $mode = self::CREATION_MODE);

    /**
     * @param string $path
     * @return \React\Promise\PromiseInterface
     */
    public function rmdir($path);

    /**
     * @param string $filename
     * @return \React\Promise\PromiseInterface
     */
    public function unlink($filename);

    /**
     * @param string $path
     * @param int $mode
     * @return \React\Promise\PromiseInterface
     */
    public function chmod($path, $mode);

    /**
     * @param string $path
     * @param int $uid
     * @param int $gid
     * @return \React\Promise\PromiseInterface
     */
    public function chown($path, $uid, $gid);

    /**
     * @param string $filename
     * @return \React\Promise\PromiseInterface
     */
    public function stat($filename);

    /**
     * @param string $path
     * @param int $flags
     * @return \React\Promise\PromiseInterface
     */
    public function ls($path, $flags = EIO_READDIR_DIRS_FIRST);

    /**
     * @param string $path
     * @param $mode
     * @return \React\Promise\PromiseInterface
     */
    public function touch($path, $mode = self::CREATION_MODE);

    /**
     * @param string $path
     * @param string $flags
     * @param $mode
     * @return \React\Promise\PromiseInterface
     */
    public function open($path, $flags, $mode = self::CREATION_MODE);

    /**
     * @param resource $fd
     * @return \React\Promise\PromiseInterface
     */
    public function close($fd);
}
