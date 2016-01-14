<?php

use React\Promise\FulfilledPromise;

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$filesystem = \React\Filesystem\Filesystem::create($loop);
echo 'Using ', get_class($filesystem->getAdapter()), PHP_EOL;
$filesystem->file(__FILE__)->exists()->then(function () use ($filesystem) {
    echo 'File "' . __FILE__ . '" exists', PHP_EOL;
    return new FulfilledPromise($filesystem);
}, function ($e) {
    echo $e->getMessage(), PHP_EOL;
})->then(function ($filesystem) {
    $fakeFile = __FILE__ . time();
    $filesystem->file($fakeFile)->exists()->then(null, function () use($fakeFile) {
        echo 'File "' . $fakeFile . '" doesn\'t exists', PHP_EOL;
    }, function ($e) {
        echo $e->getMessage(), PHP_EOL;
    });
});
$loop->run();
