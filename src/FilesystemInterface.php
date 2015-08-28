<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;
use React\Filesystem\Node;

interface FilesystemInterface
{
    /**
     * @param LoopInterface $loop
     * @return static
     * @throws NoAdapterException
     */
    public static function create(LoopInterface $loop);

    /**
     * @param AdapterInterface $adapter
     * @return static
     */
    public static function createFromAdapter(AdapterInterface $adapter);

    /**
     * @return AdapterInterface
     */
    public function getAdapter();

    /**
     * @param string $filename
     * @return Node\File
     */
    public function file($filename);

    /**
     * @param string $path
     * @return Node\Directory
     */
    public function dir($path);

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
