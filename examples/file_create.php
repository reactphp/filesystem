<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'react_filesystem_file_touch_' . uniqid();
$file = \React\Filesystem\Filesystem::create($loop)->file($fileName);
$file->create()
->then(function () use ($file, $fileName) {
    echo 'File "' . $fileName . '" created.', PHP_EOL;
    return $file->stat();
})
->then(function ($data) use ($file) {
    echo 'stat data: ', var_export($data, true), PHP_EOL;
    return $file->remove();
})
->then(function () {
    echo 'File removed', PHP_EOL;
    echo 'Done!', PHP_EOL;
}, function ($e) {
    die($e->getMessage() . PHP_EOL);
});

$loop->run();
