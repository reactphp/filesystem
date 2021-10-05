<?php

use React\Filesystem\Factory;
use React\Filesystem\Node\FileInterface;

require 'vendor/autoload.php';

Factory::create()->detect(__FILE__ . '.copy')->then(static function (FileInterface $file) {
    return $file->putContents(file_get_contents(__FILE__));
})->then(static function ($result): void {
    var_export([$result]);
})->done();
