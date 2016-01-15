<?php

function formatBytes($bytes, $precision = 2)
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}

use React\Filesystem\Node\File;
use React\Filesystem\Node\NodeInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$i = 0;
$filesystem = \React\Filesystem\Filesystem::create($loop, [
    'open_file_limit' => 8,
]);
echo 'Using ', get_class($filesystem->getAdapter()), PHP_EOL;
$dir = $filesystem->dir(__DIR__);
$stream = $dir->lsRecursiveStreaming();
$stream->on('data', function (NodeInterface $node) use (&$i) {
    if ($node instanceof File) {
        $node->getContents()->then(function ($contents) use ($node, &$i) {
            echo $node->getPath(), ': ', formatBytes(strlen($contents)), PHP_EOL;
            $i++;
        }, function ($e) {
            var_export($e->getMessage());
        });
        return;
    }

    echo $node->getPath(), PHP_EOL;
    $i++;
});
$stream->on('end', function () use (&$i) {
    echo 'Found ', $i, ' nodes', PHP_EOL;
});

$loop->run();
