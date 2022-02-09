<?php

namespace React\Filesystem;

use React\EventLoop\ExtUvLoop;
use React\EventLoop\LoopInterface;
use React\Filesystem\Node\DirectoryInterface;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Node\Unknown;

final class ModeTypeDetector
{
    private const FILE = 0x8000;
    private const DIRECTORY = 0x4000;
    private const LINK = 0xa000;

    public static function detect(int $mode): string
    {
        if (($mode & self::FILE) == self::FILE) {
            return FileInterface::class;
        }

        if (($mode & self::DIRECTORY) == self::DIRECTORY) {
            return DirectoryInterface::class;
        }

        return Unknown::class;
    }
}
