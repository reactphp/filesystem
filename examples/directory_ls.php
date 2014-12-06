<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

\React\Filesystem\Filesystem::create($loop)->dir(__DIR__)->ls()->then(function ($list) {
    foreach ($list as $node) {
        echo $node->getPath(), PHP_EOL;
    }
});

$loop->run();
