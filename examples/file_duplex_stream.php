<?php

die('This example\'s underlying required code isn\'t ready yet.')

echo 'Warning this example uses 850MB in disk space and at least that in memory. If your machine can\'t handle that please kill this example now. It will start in 10 seconds otherwise.', PHP_EOL, PHP_EOL, PHP_EOL;
sleep(10);
echo 'Starting';
usleep(25000);
echo '.';
usleep(25000);
echo '.';
usleep(25000);
echo '.';
usleep(25000);
echo '.', PHP_EOL;

$generatedFileContents = '';
$readedFileContents = '';
for ($i = 0; $i <= 100000000; $i++) {
    $generatedFileContents .= $i . PHP_EOL;
}

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'react_filesystem_file_duplex_stream_' . uniqid();
echo $fileName;
$file = \React\Filesystem\Filesystem::create($loop)->file($fileName);
$file->open('c+')
->then(function (\React\Filesystem\Stream\DuplexStreamInterface $stream) use ($file, $fileName, $generatedFileContents, &$readedFileContents) {
    $stream->write($generatedFileContents);
    $stream->on('data', function ($data) use (&$readedFileContents) {
        $readedFileContents .= $data;
    });
    $stream->on('end', function (\React\Filesystem\Stream\DuplexStreamInterface $stream) {
        $stream->close();
    });
    $stream->resume();
});

$file->remove();

$loop->run();

if ($generatedFileContents == $readedFileContents) {
    echo 'Contents read from disk match generated contents', PHP_EOL;
} else {
    echo 'Contents read from disk DON\'T match generated contents', PHP_EOL;
}
