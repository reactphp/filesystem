<?php

use React\Filesystem\Factory;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Node\NotExistInterface;
use React\Filesystem\Stat;
use React\Promise\PromiseInterface;

require 'vendor/autoload.php';

Factory::create()->detect(sys_get_temp_dir() . __FILE__ . time() . time() . time() . time() . time() . time())->then(static function (NotExistInterface $node): PromiseInterface {
    return $node->createFile();
})->then(static function (FileInterface $file): PromiseInterface {
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
