<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;
use React\Filesystem\Node;

class Filesystem
{
    protected $filesystem;

    /**
     * @param LoopInterface $loop
     * @param AdapterInterface $adapter
     * @return static
     * @throws NoAdapterException
     */
    public static function create(LoopInterface $loop, AdapterInterface $adapter = null)
    {
        if ($adapter instanceof AdapterInterface) {
            return new static($adapter);
        }

        if (extension_loaded('eio')) {
            return new static(new EioAdapter($loop));
        }

        throw new NoAdapterException();
    }

    /**
     * @param AdapterInterface $filesystem
     */
    private function __construct(AdapterInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $filename
     * @return Node\File
     */
    public function file($filename)
    {
        return new Node\File($filename, $this->filesystem);
    }

    /**
     * @param string $path
     * @return Node\Directory
     */
    public function dir($path)
    {
        return new Node\Directory($path, $this->filesystem);
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
        $this->filesystem->setInvoker($invoker);
    }
}
