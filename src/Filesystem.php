<?php

namespace React\Filesystem;

use React\Filesystem\Node;

class Filesystem
{

    public static function create($loop)
    {
        return new static($loop);
    }

    public function __construct($loop, AdapterInterface $filesystem = null)
    {
        $this->loop = $loop;

        if ($filesystem === null) {
            $filesystem = new EioAdapter($loop);
        }
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
