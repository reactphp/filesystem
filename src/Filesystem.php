<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;
use React\Filesystem\Node;

class Filesystem
{
    protected $filesystem;

    /**
     * @param LoopInterface $loop
     * @return Filesystem
     */
    public static function create(LoopInterface $loop)
    {
        return new static(new EioAdapter($loop));
    }

    /**
     * @param AdapterInterface $filesystem
     */
    public function __construct(AdapterInterface $filesystem)
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
}
