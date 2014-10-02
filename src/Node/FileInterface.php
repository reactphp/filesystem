<?php

namespace React\Filesystem\Node;

use React\Filesystem\FilesystemInterface;

interface FileInterface
{
    public function __construct($filename, FilesystemInterface $filesystem);

    public function exists();

    public function remove();

    public function open($flags);

    public function time();

    public function rename($toFilename);

    public function size();
}
