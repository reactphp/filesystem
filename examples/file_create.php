<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'react_filesystem_file_touch_' . uniqid();
$filesystem = \React\Filesystem\Filesystem::create($loop);
echo 'Using ', get_class($filesystem->getAdapter()), PHP_EOL;
$file = $filesystem->file($fileName);
$file->create()
->then(function () use ($file, $fileName) {
    echo 'File "' . $fileName . '" created.', PHP_EOL;
    return $file->stat();
})
->then(function ($data) use ($file) {
    echo 'stat data: ', PHP_EOL;
    foreach ($data as $key => $value) {
        echo "\t", $key, ': ', $value, PHP_EOL;
    }
    return $file->remove();
})
->then(function () {
    echo 'File removed', PHP_EOL;
    echo 'Done!', PHP_EOL;
}, function ($e) {
    echo $e->getMessage(), PHP_EOL;
});

$loop->run();
