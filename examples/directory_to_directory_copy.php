<?php

use React\Filesystem\Node\NodeInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$filesystem = \React\Filesystem\Filesystem::create($loop);
echo 'Using ', get_class($filesystem->getAdapter()), PHP_EOL;
$from = $filesystem->dir(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'vendor');
$to = $filesystem->dir(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'react_filesystem_file_to_file_copy' . DIRECTORY_SEPARATOR . uniqid());
echo 'From: ', $from->getPath(), PHP_EOL;
echo 'To: ', $to->getPath(), PHP_EOL;
$to->createRecursive()->then(function () use ($from, $to) {
    $i = 0;
    $stream = $from->copyStreaming($to);
    $stream->on('data', function (NodeInterface $node) use (&$i) {
        echo $node->getPath(), PHP_EOL;
        $i++;
    });
    $stream->on('end', function () use (&$i) {
        echo 'Copied ', $i, ' nodes', PHP_EOL;
    });
});

$loop->run();
