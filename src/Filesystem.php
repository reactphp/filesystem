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
     * @param array $options
     * @return FilesystemInterface
     */
    public static function create(LoopInterface $loop, array $options = [])
    {
        if (extension_loaded('eio')) {
            return static::setFilesystemOnAdapter(static::createFromAdapter(new Eio\Adapter($loop, $options)));
        }

        return static::setFilesystemOnAdapter(static::createFromAdapter(new ChildProcess\Adapter($loop, $options)));
    }

    /**
     * @param AdapterInterface $adapter
     * @return FilesystemInterface
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
     * Filesystem constructor.
     * @param AdapterInterface $adapter
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
     * @return Node\FileInterface
     */
    public function file($filename)
    {
        return new Node\File($filename, $this);
    }

    /**
     * @param string $path
     * @return Node\DirectoryInterface
     */
    public function dir($path)
    {
        return new Node\Directory($path, $this);
    }

    /**
     * @param string $path
     * @param Node\NodeInterface $destination
     * @return Node\LinkInterface
     */
    public function link($path, Node\NodeInterface $destination)
    {
        return new Node\Link($path, $destination, $this);
    }

    /**
     * @param string $path
     * @return \React\Promise\PromiseInterface
     */
    public function constructLink($path)
    {
        return $this->adapter->readlink($path)->then(function ($linkPath) {
            return $this->adapter->detectType($linkPath);
        })->then(function (Node\NodeInterface $destination) use ($path) {
            return \React\Promise\resolve($this->link($path, $destination));
        });
    }

    /**
     * @param string $filename
     * @return \React\Promise\PromiseInterface
     */
    public function getContents($filename)
    {
        $file = $this->file($filename);
        return $file->exists()->then(function () use ($file) {
            return $file->getContents();
        });
    }

    /**
     * @param CallInvokerInterface $invoker
     */
    public function setInvoker(CallInvokerInterface $invoker)
    {
        $this->adapter->setInvoker($invoker);
    }
}
