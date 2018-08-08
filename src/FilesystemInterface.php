<?php

namespace React\Filesystem;

use RuntimeException;
use React\EventLoop\LoopInterface;
use React\Filesystem\Node;

interface FilesystemInterface
{
    /**
     * @param LoopInterface $loop
     * @param array $options
     * @return FilesystemInterface
     * @throws RuntimeException
     */
    public static function create(LoopInterface $loop, array $options = []);

    /**
     * @param AdapterInterface $adapter
     * @return static
     */
    public static function createFromAdapter(AdapterInterface $adapter);

    /**
     * @return string[]
     */
    public static function getSupportedAdapters();

    /**
     * @return AdapterInterface
     */
    public function getAdapter();

    /**
     * @param string $filename
     * @return Node\FileInterface
     */
    public function file($filename);

    /**
     * @param string $path
     * @return Node\DirectoryInterface
     */
    public function dir($path);

    /**
     * @param string $path
     * @param Node\NodeInterface $destination
     * @return Node\LinkInterface
     */
    public function link($path, Node\NodeInterface $destination);

    /**
     * @param string $filename
     * @return \React\Promise\PromiseInterface
     */
    public function getContents($filename);

    /**
     * @param CallInvokerInterface $invoker
     */
    public function setInvoker(CallInvokerInterface $invoker);
}
