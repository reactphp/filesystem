<?php

use React\Filesystem\Factory;
use React\Filesystem\Node\DirectoryInterface;
use React\Filesystem\Node\NodeInterface;

require 'vendor/autoload.php';

Factory::create()->detect(__DIR__)->then(function (DirectoryInterface $directory) {
    return $directory->ls();
})->then(static function ($nodes) {
    foreach ($nodes as $node) {
        assert($node instanceof NodeInterface);
        echo $node->name(), ': ', get_class($node), PHP_EOL;
    }
    echo '----------------------------', PHP_EOL, 'Done listing directory', PHP_EOL;
}, function (Throwable $throwable) {
    echo $throwable;
});
