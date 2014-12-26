<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

\React\Filesystem\Filesystem::create($loop)->file(__FILE__)->size()->then(function ($size) {
    echo 'File "' . __FILE__ . '" is ' . $size . ' bytes', PHP_EOL;
}, function ($e) {
    die($e->getMessage() . PHP_EOL);
});

$loop->run();
