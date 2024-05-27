<?php

use React\EventLoop\Loop;
use React\Filesystem\Factory;
use React\Filesystem\Node\FileInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\ThroughStream;

require 'vendor/autoload.php';

function streamFile(FileInterface $file, ReadableStreamInterface $readableStream): void
{
    $buffer = '';
    $writing = false;

    $readableStream->on('data', function ($data) use (&$buffer, &$writing, $file): void {
        $buffer .= $data;

        if ($writing === false) {
            $writing = true;
            $writeData = $buffer;
            $buffer = '';
            $file->putContents($writeData, FILE_APPEND)->then(function (int $bytesWritten) use (&$buffer, &$writing, $writeData): void {
                $writing = false;
                $buffer = substr($writeData, $bytesWritten) . $buffer;
            })->done();
        }
    });
}

file_put_contents(__FILE__ . '.time', '', FILE_APPEND);
Factory::create()->detect(__FILE__ . '.time')->then(function (FileInterface $file) {
    $stream = new ThroughStream();
    streamFile($file, $stream);
    for ($i = 0; $i < 13; $i++) {
        Loop::addTimer($i, function () use ($stream): void {
            $stream->write(time() . PHP_EOL);
        });
    }
    Loop::addTimer(15, function () use ($stream): void {
        $stream->end(time() . PHP_EOL);
    });
})->done();
