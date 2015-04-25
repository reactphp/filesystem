<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

\React\Filesystem\Filesystem::create($loop)->dir(dirname(__DIR__))->size()->then(function ($size) {
    echo 'Directory "' . dirname(__DIR__) . '" contains ' . $size['directories'] . ' directories, ' . $size['files'] . ' files and is ' . $size['size'] . ' bytes in size', PHP_EOL;
}, function ($e) {
    echo $e->getMessage(), PHP_EOL;
});

$loop->run();
