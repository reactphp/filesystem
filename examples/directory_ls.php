<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$filesystem = \React\Filesystem\Filesystem::create($loop);
echo 'Using ', get_class($filesystem->getAdapter()), PHP_EOL;
$filesystem->dir(__DIR__ . DIRECTORY_SEPARATOR . 'tmp')->ls()->then(function (\SplObjectStorage $list) {
    foreach ($list as $node) {
        echo get_class($node), ': ', $node->getPath(), PHP_EOL;
    }
}, function ($e) {
    echo $e->getMessage(), PHP_EOL;
});

$loop->run();
