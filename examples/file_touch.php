<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$timesFunction = function ($times) {
    $nextLine = "\r\n\t";
    echo 'File "' . __FILE__ . '":';
    echo $nextLine;
    echo 'Access timestamp: ' . $times['atime'];
    echo $nextLine;
    echo 'Creation timestamp: ' . $times['ctime'];
    echo $nextLine;
    echo 'Modified timestamp: ' . $times['mtime'];
    echo "\r\n";
};

$filesystem = \React\Filesystem\Filesystem::create($loop);
echo 'Using ', get_class($filesystem->getAdapter()), PHP_EOL;
$file = $filesystem->file(__FILE__);

$file->time()->then($timesFunction)->then(function () use ($file) {
    return $file->touch();
})->then(function () use ($file) {
    return $file->time();
})->then($timesFunction)->then(null, function ($e) {
    echo $e->getMessage(), PHP_EOL;
});

$loop->run();
