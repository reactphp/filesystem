<?php

echo 'Warning this example uses 850MB in disk space and at least that in memory. If your machine can\'t handle that please kill this example now otherwise it will start in 10 seconds otherwise.', PHP_EOL, PHP_EOL, PHP_EOL;
usleep(10000000);
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
$filesystem = \React\Filesystem\Filesystem::create($loop);
echo 'Using ', get_class($filesystem->getAdapter()), PHP_EOL;
$file = $filesystem->file($fileName);
$file->open('ct+')
->then(function (\React\Filesystem\Stream\DuplexStreamInterface $stream) use ($loop, $file, $fileName, $generatedFileContents, &$readedFileContents) {
    $stream->on('end', function ($stream) use ($generatedFileContents, &$readedFileContents) {
        if (strlen($generatedFileContents) != strlen($readedFileContents)) {
            $stream->resume();
        }
    });
    $stream->on('data', function ($data) use (&$readedFileContents) {
        $readedFileContents .= $data;
    });
    $stream->resume();
    $stream->write($generatedFileContents);
});

$loop->run();
$file->remove();
$loop->run();

if ($generatedFileContents == $readedFileContents) {
    echo 'Contents read from disk match generated contents', PHP_EOL;
} else {
    echo 'Contents read from disk DON\'T match generated contents', PHP_EOL;
}
