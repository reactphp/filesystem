<?php

namespace React\Filesystem;

use React\Filesystem\Eio\BufferedSink;
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

    public function getContents($filename)
    {
        return $this->file($filename)->open(EIO_O_RDONLY)->then(function($stream) {
            return BufferedSink::createPromise($stream);
        });
    }
}
