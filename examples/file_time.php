<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

\React\Filesystem\Filesystem::create($loop)->file(__FILE__)->time()->then(function ($times) {
    $nextLine = "\r\n\t";
    echo 'File "' . __FILE__ . '":';
    echo $nextLine;
    echo 'Access timestamp: ' . $times['atime'];
    echo $nextLine;
    echo 'Creation timestamp: ' . $times['ctime'];
    echo $nextLine;
    echo 'Modified timestamp: ' . $times['mtime'];
    echo "\r\n";
}, function ($e) {
    die($e->getMessage() . PHP_EOL);
});

$loop->run();
