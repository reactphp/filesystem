<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;

interface AdapterInterface
{
    const CREATION_MODE = 'rwxrwx---';

    /**
     * @param LoopInterface $loop
     * @param array $options
     */
    public function __construct(LoopInterface $loop, array $options = []);

    /**
     * @return LoopInterface
     */
    public function getLoop();

    /**
     * @param FilesystemInterface $filesystem
     * @return void
     */
    public function setFilesystem(FilesystemInterface $filesystem);

    /**
     * @param CallInvokerInterface $invoker
     * @return void
     */
    public function setInvoker(CallInvokerInterface $invoker);

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
     * @param $fileDescriptor
     * @param int $length
     * @param int $offset
     * @return \React\Promise\PromiseInterface
     */
    public function read($fileDescriptor, $length, $offset);

    /**
     * @param $fileDescriptor
     * @param string $data
     * @param int $length
     * @param int $offset
     * @return \React\Promise\PromiseInterface
     */
    public function write($fileDescriptor, $data, $length, $offset);

    /**
     * @param resource $fd
     * @return \React\Promise\PromiseInterface
     */
    public function close($fd);

    /**
     * @param string $fromPath
     * @param string $toPath
     * @return \React\Promise\PromiseInterface
     */
    public function rename($fromPath, $toPath);
}
