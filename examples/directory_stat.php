<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

\React\Filesystem\Filesystem::create($loop)->dir(__DIR__)->stat()->then(function ($data) {
    var_export($data);
    echo PHP_EOL;
}, function ($e) {
    die($e->getMessage() . PHP_EOL);
});

$loop->run();
