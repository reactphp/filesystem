<?php

use React\Filesystem\Factory;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Stat;

require 'vendor/autoload.php';

Factory::create()->detect(__FILE__)->then(function (FileInterface $file) {
    return $file->stat();
})->then(static function (Stat $stat): void {
    echo $stat->path(), ': ', get_class($stat), PHP_EOL;
    echo 'Mode: ', $stat->mode(), PHP_EOL;
    echo 'Uid: ', $stat->uid(), PHP_EOL;
    echo 'Gid: ', $stat->gid(), PHP_EOL;
    echo 'Size: ', $stat->size(), PHP_EOL;
    echo 'Atime: ', $stat->atime()->format(DATE_ISO8601), PHP_EOL;
    echo 'Mtime: ', $stat->mtime()->format(DATE_ISO8601), PHP_EOL;
    echo 'Ctime: ', $stat->ctime()->format(DATE_ISO8601), PHP_EOL;
})->done();
