<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$filesystem = \React\Filesystem\Filesystem::create($loop);
echo 'Using ', get_class($filesystem->getAdapter()), PHP_EOL;
foreach ([
    'examples',
    'src',
    'tests',
    'vendor',
] as $directory) {
    $path = dirname(__DIR__) . '/' . $directory;
    $filesystem->dir($path)->sizeRecursive()->then(function ($size) use ($path) {
        echo 'Directory "' . $path . '" contains ' . $size['directories'] . ' directories, ' . $size['files'] . ' files and is ' . $size['size'] . ' bytes in size', PHP_EOL;
    }, function ($e) {
        echo $e->getMessage(), PHP_EOL, var_export($e->getArgs(), true), PHP_EOL;
    });

}

$loop->run();
