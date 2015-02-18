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

$file = \React\Filesystem\Filesystem::create($loop)->file(__FILE__);

$file->time()->then($timesFunction)->then(function () use ($file) {
    return $file->touch();
})->then(function () use ($file) {
    return $file->time();
})->then($timesFunction)->then(null, function ($e) {
    die($e->getMessage() . PHP_EOL);
});

$loop->run();
