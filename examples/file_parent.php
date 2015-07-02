<?php


require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$node = \React\Filesystem\Filesystem::create($loop)->file(__FILE__);

do {
    echo $node->getName(), PHP_EOL;
} while ($node = $node->getParent());
