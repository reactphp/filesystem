<?php
const SHOW_DATA = true;
require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

\React\Filesystem\Filesystem::create($loop)->getAdapter()->readlink('link_get_contents.php')->then(function ($contents) {
    echo $contents, PHP_EOL;
}, function ($e) {
    echo $e->getMessage(), PHP_EOL;
});

$loop->run();
