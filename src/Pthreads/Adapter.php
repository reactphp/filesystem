<?php

namespace React\Filesystem\Pthreads;

use React\EventLoop\LoopInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\CallInvokerInterface;
use React\Filesystem\FilesystemInterface;

class Adapter implements AdapterInterface
{
    /**
     * @inheritDoc
     */
    public function __construct(LoopInterface $loop, array $options = [])
    {
        // TODO: Implement __construct() method.
    }

    /**
     * @inheritDoc
     */
    public function getLoop()
    {
        // TODO: Implement getLoop() method.
    }

    /**
     * @inheritDoc
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        // TODO: Implement setFilesystem() method.
    }

    /**
     * @inheritDoc
     */
    public function setInvoker(CallInvokerInterface $invoker)
    {
        // TODO: Implement setInvoker() method.
    }

    /**
     * @inheritDoc
     */
    public function callFilesystem($function, $args, $errorResultCode = -1)
    {
        // TODO: Implement callFilesystem() method.
    }

    /**
     * @inheritDoc
     */
    public function mkdir($path, $mode = self::CREATION_MODE)
    {
        // TODO: Implement mkdir() method.
    }

    /**
     * @inheritDoc
     */
    public function rmdir($path)
    {
        // TODO: Implement rmdir() method.
    }

    /**
     * @inheritDoc
     */
    public function unlink($filename)
    {
        // TODO: Implement unlink() method.
    }

    /**
     * @inheritDoc
     */
    public function chmod($path, $mode)
    {
        // TODO: Implement chmod() method.
    }

    /**
     * @inheritDoc
     */
    public function chown($path, $uid, $gid)
    {
        // TODO: Implement chown() method.
    }

    /**
     * @inheritDoc
     */
    public function stat($filename)
    {
        // TODO: Implement stat() method.
    }

    /**
     * @inheritDoc
     */
    public function ls($path, $flags = EIO_READDIR_DIRS_FIRST)
    {
        // TODO: Implement ls() method.
    }

    /**
     * @inheritDoc
     */
    public function touch($path, $mode = self::CREATION_MODE)
    {
        // TODO: Implement touch() method.
    }

    /**
     * @inheritDoc
     */
    public function open($path, $flags, $mode = self::CREATION_MODE)
    {
        // TODO: Implement open() method.
    }

    /**
     * @inheritDoc
     */
    public function read($fileDescriptor, $length, $offset)
    {
        // TODO: Implement read() method.
    }

    /**
     * @inheritDoc
     */
    public function write($fileDescriptor, $data, $length, $offset)
    {
        // TODO: Implement write() method.
    }

    /**
     * @inheritDoc
     */
    public function close($fd)
    {
        // TODO: Implement close() method.
    }

    /**
     * @inheritDoc
     */
    public function rename($fromPath, $toPath)
    {
        // TODO: Implement rename() method.
    }

    /**
     * @inheritDoc
     */
    public function readlink($path)
    {
        // TODO: Implement readlink() method.
    }

    /**
     * @inheritDoc
     */
    public function symlink($fromPath, $toPath)
    {
        // TODO: Implement symlink() method.
    }

    /**
     * @inheritDoc
     */
    public function detectType($path)
    {
        // TODO: Implement detectType() method.
    }
}
