<?php

namespace React\Filesystem\Filesystem;

use React\EventLoop\LoopInterface;

interface FilesystemInterface {
    public function __construct(LoopInterface $loop);
    //public function mkdir();
    //public function rmdir();
    public function unlink($filename);
    public function chmod($path, $mode);
    public function chown($path, $uid, $guid);
    public function stat($filename);
    //public function ls();
    //public function open();
    //public function read();
    //public function write();
}
