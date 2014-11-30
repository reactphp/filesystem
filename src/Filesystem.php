<?php

namespace React\Filesystem;

use React\Filesystem\Node;

class Filesystem
{
    protected $filesystem;

    public static function create($loop)
    {
        return new static(new EioAdapter($loop));
    }

    public function __construct(AdapterInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function file($filename)
    {
        return new Node\File($filename, $this->filesystem);
    }

    public function dir($path)
    {
        return new Node\Directory($path, $this->filesystem);
    }

    public function getContents($filename)
    {
        return $this->file($filename)->getContents();
    }
}
