<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$start = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'react_filesystem_dir_create' . DIRECTORY_SEPARATOR . uniqid();
$dirName = $start . DIRECTORY_SEPARATOR . uniqid() . DIRECTORY_SEPARATOR . uniqid();
$filesystem = \React\Filesystem\Filesystem::create($loop);
echo 'Using ', get_class($filesystem->getAdapter()), PHP_EOL;
$startDir = $filesystem->dir($start);
$dir = $filesystem->dir($dirName);
echo 'Creating directory: ' . $dirName, PHP_EOL;
$dir->createRecursive('rwxrwx---')->then(function () use ($startDir) {
    return $startDir->lsRecursive();
})->then(function (\SplObjectStorage $list) {
    foreach ($list as $node) {
        echo $node->getPath(), PHP_EOL;
    }
}, function ($e) {
    echo $e->getMessage(), PHP_EOL;
});

$loop->run();

echo 'Don\'t forget to clean up!', PHP_EOL;
