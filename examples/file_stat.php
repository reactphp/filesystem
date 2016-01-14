<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$filesystem = \React\Filesystem\Filesystem::create($loop);
echo 'Using ', get_class($filesystem->getAdapter()), PHP_EOL;
$filesystem->file(__FILE__)->stat()->then(function ($data) {
    foreach ($data as $key => $value) {
        echo $key, ': ', var_export($value, true), PHP_EOL;
    }
}, function ($e) {
    echo $e->getMessage(), PHP_EOL;
});

$loop->run();
