<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$contents = 'abcdefghijklopqrstuvwxyz';
$filename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'alpha.bet';

$filesystem = \React\Filesystem\Filesystem::create($loop);
echo 'Using ', get_class($filesystem->getAdapter()), PHP_EOL;
$filesystem->file($filename)->putContents($contents)->then(function ($contents) use ($filename) {
    echo file_get_contents($filename), PHP_EOL;
}, function (Exception $e) {
    echo $e->getMessage(), PHP_EOL;
    echo $e->getTraceAsString(), PHP_EOL;
});

$loop->run();
