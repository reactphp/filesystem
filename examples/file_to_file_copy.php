<?php


use React\Filesystem\Node\FileInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$filesystem = \React\Filesystem\Filesystem::create($loop);
echo 'Using ', get_class($filesystem->getAdapter()), PHP_EOL;
$from = $filesystem->file(__FILE__);
$to = $filesystem->file(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'react_filesystem_file_to_file_copy_' . uniqid());

$from->copy($to)->then(function (FileInterface $file) {
    echo $file->getPath(), PHP_EOL;
    return $file->stat();
})->then(function ($stats) {
    var_export($stats);
    echo PHP_EOL;
});

$loop->run();
