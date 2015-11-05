<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

\React\Filesystem\Filesystem::create($loop)->file('test.txt')->chown(1000, 1000)->then(function ($result) {
    var_export($result);
    echo PHP_EOL;
}, function ($e) {
    echo $e->getMessage(), PHP_EOL;
});

$loop->run();
