<?php

use React\Filesystem\Factory;
use React\Filesystem\Node\FileInterface;

require 'vendor/autoload.php';

Factory::create()->detect(__FILE__)->then(function (FileInterface $file) {
    return $file->getContents();
})->then(static function (string $contents): void {
    echo $contents;
})->done();
