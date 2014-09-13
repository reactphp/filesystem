<?php

namespace React\Filesystem\Filesystem;

use React\EventLoop\LoopInterface;

interface FilesystemInterface {
    public function __construct(LoopInterface $loop);
    public function mkdir();
    public function rmdir();
    public function unlink();
    public function chmod();
    public function chown();
    public function stat();
    public function time();
    public function ls();
    public function open();
    public function read();
    public function write();
}
