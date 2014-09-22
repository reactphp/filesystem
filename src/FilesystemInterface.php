<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;

interface FilesystemInterface
{
    public function __construct(LoopInterface $loop);

    public function mkdir($path, $mode = self::CREATION_MODE);

    public function rmdir($path);

    public function unlink($filename);

    public function chmod($path, $mode);

    public function chown($path, $uid, $gid);

    public function stat($filename);

    public function ls($path, $flags = EIO_READDIR_DIRS_FIRST);

    public function touch($path, $mode = self::CREATION_MODE);

    public function open($path, $flags, $mode = self::CREATION_MODE);

    public function close($fd);
}
