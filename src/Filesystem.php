<?php

namespace React\Filesystem;

use React\Filesystem\Filesystem\EioFilesystem;

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
        return new File($filename, $this->filesystem);
    }

    public function dir($path)
    {
        return new Directory($path, $this->filesystem);
    }
}
