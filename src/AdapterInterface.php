<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

interface AdapterInterface
{
    const CREATION_MODE = 'rwxrw----';
    
    /**
     * Checks whether the current installation supports the adapter.
     *
     * @return boolean
     */
    public static function isSupported();

    /**
     * Return the loop associated with this adapter.
     *
     * @return LoopInterface
     */
    public function getLoop();

    /**
     * Set the relevant filesystem for this adapter.
     *
     * @internal
     * @param FilesystemInterface $filesystem
     * @return void
     */
    public function setFilesystem(FilesystemInterface $filesystem);

    /**
     * Set the call invoker for this adapter.
     *
     * @param CallInvokerInterface $invoker
     * @return void
     */
    public function setInvoker(CallInvokerInterface $invoker);

    /**
     * Call the underlying filesystem.
     *
     * @internal
     * @param string $function
     * @param array $args
     * @param int $errorResultCode
     * @return PromiseInterface
     */
    public function callFilesystem($function, $args, $errorResultCode = -1);

    /**
     * Create a directory at the given path with the given mode.
     *
     * @param string $path
     * @param $mode
     * @return PromiseInterface
     */
    public function mkdir($path, $mode = self::CREATION_MODE);

    /**
     * Remove the given directory, fails when it has contents.
     *
     * @param string $path
     * @return PromiseInterface
     */
    public function rmdir($path);

    /**
     * Remove the given file.
     *
     * @param string $filename
     * @return PromiseInterface
     */
    public function unlink($filename);

    /**
     * Change the mode of the given path.
     *
     * @param string $path
     * @param int $mode
     * @return PromiseInterface
     */
    public function chmod($path, $mode);

    /**
     * Change the owner of the given path.
     *
     * @param string $path
     * @param int $uid
     * @param int $gid
     * @return PromiseInterface
     */
    public function chown($path, $uid, $gid);

    /**
     * Stat the node, returning information such as the file, c/m/a-time, mode, g/u-id, and more.
     *
     * @param string $filename
     * @return PromiseInterface
     */
    public function stat($filename);

    /**
     * List contents of the given path.
     *
     * @param string $path
     * @return PromiseInterface
     */
    public function ls($path);

    /**
     * Touch the given path, either creating a file, or updating mtime on the file.
     *
     * @param string $path
     * @param $mode
     * @return PromiseInterface
     */
    public function touch($path, $mode = self::CREATION_MODE);

    /**
     * Open a file for reading or writing at the given path. This will return a file descriptor,
     * which can be used to read or write to the file. And ultimately close the file descriptor.
     *
     * @param string $path
     * @param string $flags
     * @param $mode
     * @return PromiseInterface<file descriptor>
     */
    public function open($path, $flags, $mode = self::CREATION_MODE);

    /**
     * Read from the given file descriptor.
     *
     * @param $fileDescriptor
     * @param int $length
     * @param int $offset
     * @return PromiseInterface
     */
    public function read($fileDescriptor, $length, $offset);

    /**
     * Write to the given file descriptor.
     *
     * @param $fileDescriptor
     * @param string $data
     * @param int $length
     * @param int $offset
     * @return PromiseInterface
     */
    public function write($fileDescriptor, $data, $length, $offset);

    /**
     * Close the given file descriptor.
     *
     * @param resource $fd
     * @return PromiseInterface
     */
    public function close($fd);

    /**
     * Rename a node.
     *
     * @param string $fromPath
     * @param string $toPath
     * @return PromiseInterface
     */
    public function rename($fromPath, $toPath);

    /**
     * Read link information from the given path (has to be a symlink).
     *
     * @param string $path
     * @return PromiseInterface
     */
    public function readlink($path);

    /**
     * Create a symlink from $fromPath to $toPath.
     *
     * @param string $fromPath
     * @param string $toPath
     * @return PromiseInterface
     */
    public function symlink($fromPath, $toPath);

    /**
     * Detect the type of the given path.
     *
     * @param string $path
     * @return PromiseInterface
     */
    public function detectType($path);
}
