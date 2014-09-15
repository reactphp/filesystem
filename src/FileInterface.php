<?php

namespace React\Filesystem;

interface FileInterface {
    public function __construct($filename, Filesystem $filesystem);
    public function exists();
    public function remove();
    public function open();
    public function time();
    public function move();
    public function size();
}
