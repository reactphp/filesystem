<?php

namespace React\Filesystem;

use React\Filesystem\Node;

class Filesystem
{

    public static function create($loop)
    {
        return new static($loop);
    }

    public function __construct($loop)
    {
        $this->loop = $loop;
        $this->filesystem = new EioFilesystem($loop);
    }

    public function file($filename)
    {
        return new Node\File($filename, $this->filesystem);
    }

    public function dir($path)
    {
        return new Node\Directory($path, $this->filesystem);
    }
}
