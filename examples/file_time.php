<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$filesystem = \React\Filesystem\Filesystem::create($loop);
echo 'Using ', get_class($filesystem->getAdapter()), PHP_EOL;
$filesystem->file(__FILE__)->time()->then(function ($times) {
    $nextLine = "\r\n\t";
    echo 'File "' . __FILE__ . '":';
    echo $nextLine;
    echo 'Access timestamp: ' . $times['atime']->format('r');
    echo $nextLine;
    echo 'Creation timestamp: ' . $times['ctime']->format('r');
    echo $nextLine;
    echo 'Modified timestamp: ' . $times['mtime']->format('r');
    echo "\r\n";
}, function ($e) {
    echo $e->getMessage(), PHP_EOL;
});

$loop->run();
