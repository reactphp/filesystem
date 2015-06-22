<?php

use React\Filesystem\Node\NodeInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

\React\Filesystem\Filesystem::create($loop)->dir(__DIR__)->ls()->then(function (\SplObjectStorage $list) {
    echo 'Found ', $list->count(), ' nodes', PHP_EOL;
}, function ($e) {
    echo $e->getMessage(), PHP_EOL;
}, function (NodeInterface $node) {
    echo $node->getPath(), PHP_EOL;
});

$loop->run();
