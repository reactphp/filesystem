<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

\React\Filesystem\Filesystem::create($loop)->dir(dirname(__DIR__))->lsRecursive()->then(function (\SplObjectStorage $list) {
    $interfaces = new RegexIterator($list, '/.*?Interface.php$/');
    foreach ($interfaces as $node) {
        echo $node->getPath(), PHP_EOL;
    }
}, function ($e) {
    echo $e->getMessage(), PHP_EOL;
});

$loop->run();
