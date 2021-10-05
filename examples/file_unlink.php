<?php

use React\Filesystem\Factory;
use React\Filesystem\Node\FileInterface;

require 'vendor/autoload.php';

$filename = tempnam(sys_get_temp_dir(), 'reactphp-filesystem-file-tail-example-');
file_put_contents($filename, file_get_contents(__FILE__));
Factory::create()->detect($filename)->then(function (FileInterface $file) use ($filename) {
    return $file->unlink();
})->done();
