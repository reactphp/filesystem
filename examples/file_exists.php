<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$filesystem = \React\Filesystem\Filesystem::create($loop);
$filesystem->file(__FILE__)->exists()->then(function () {
    echo 'File "' . __FILE__ . '" exists', PHP_EOL;
}, function ($e) {
    die($e->getMessage() . PHP_EOL);
});
$fakeFile = __FILE__ . time();
$filesystem->file($fakeFile)->exists()->then(null, function () use($fakeFile) {
    echo 'File "' . $fakeFile . '" doesn\'t exists', PHP_EOL;
}, function ($e) {
    die($e->getMessage() . PHP_EOL);
});

$loop->run();
