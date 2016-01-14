<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$filesystem = \React\Filesystem\Filesystem::create($loop);
echo 'Using ', get_class($filesystem->getAdapter()), PHP_EOL;
$filesystem->file('test.txt')->chown(1000, 1000)->then(function ($result) {
    var_export($result);
    echo PHP_EOL;
}, function ($e) {
    echo $e->getMessage(), PHP_EOL;
});

$loop->run();
