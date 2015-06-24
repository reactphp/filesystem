<?php

use React\Filesystem\Node\NodeInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$i = 0;
$dir = \React\Filesystem\Filesystem::create($loop)->dir(__DIR__);
$stream = $dir->lsStreaming();
$stream->on('data', function (NodeInterface $node) use (&$i) {
    echo $node->getPath(), PHP_EOL;
    $i++;
});
$stream->on('end', function () use (&$i) {
    echo 'Found ', $i, ' nodes', PHP_EOL;
});

$loop->run();
