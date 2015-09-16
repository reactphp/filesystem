<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;
use React\Filesystem\Node;

class Filesystem implements FilesystemInterface
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @param LoopInterface $loop
     * @return static
     * @throws NoAdapterException
     */
    public static function create(LoopInterface $loop)
    {
        if (extension_loaded('eio')) {
            return static::setFilesystemOnAdapter(static::createFromAdapter(new Eio\Adapter($loop)));
        }

        throw new NoAdapterException();
    }

    /**
     * @param AdapterInterface $adapter
     * @return static
     */
    public static function createFromAdapter(AdapterInterface $adapter)
    {
        return static::setFilesystemOnAdapter(new static($adapter));
    }

    /**
     * @param FilesystemInterface $filesystem
     * @return FilesystemInterface
     */
    protected static function setFilesystemOnAdapter(FilesystemInterface $filesystem)
    {
        $filesystem->getAdapter()->setFilesystem($filesystem);
        return $filesystem;
    }

    /**
     * @param AdapterInterface $filesystem
     */
    private function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param string $filename
     * @return Node\File
     */
    public function file($filename)
    {
        return new Node\File($filename, $this);
    }

    /**
     * @param string $path
     * @return Node\Directory
     */
    public function dir($path)
    {
        return new Node\Directory($path, $this);
    }

    /**
     * @param string $filename
     * @return \React\Promise\PromiseInterface
     */
    public function getContents($filename)
    {
        return $this->file($filename)->getContents();
    }

    /**
     * @param CallInvokerInterface $invoker
     */
    public function setInvoker(CallInvokerInterface $invoker)
    {
        $this->adapter->setInvoker($invoker);
    }
}
