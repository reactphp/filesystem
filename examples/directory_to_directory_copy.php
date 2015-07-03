<?php

use React\Filesystem\Node\DirectoryInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$filesystem = \React\Filesystem\Filesystem::create($loop);
$from = $filesystem->dir(dirname(__FILE__));
$to = $filesystem->dir(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'react_filesystem_file_to_file_copy' . DIRECTORY_SEPARATOR . uniqid());
echo 'From: ', $from->getPath(), PHP_EOL;
echo 'To: ', $to->getPath(), PHP_EOL;
$to->createRecursive()->then(function () use ($from, $to) {
    return $from->copy($to);
})->then(function (DirectoryInterface $dir) {
    echo $dir->getPath(), PHP_EOL;
    return $dir->lsRecursive();
})->then(function (\SplObjectStorage $list) {
    foreach ($list as $node) {
        echo $node->getPath(), PHP_EOL;
    }
});

$loop->run();
