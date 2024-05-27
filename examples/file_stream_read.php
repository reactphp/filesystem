<?php

use React\Filesystem\Factory;
use React\Filesystem\Node\FileInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\ThroughStream;

require 'vendor/autoload.php';

const READ_CHUNK_SIZE = 16; // Use 65536 for everything but this example

function streamFile(FileInterface $file): ReadableStreamInterface
{
    $offset = 0;
    $stream = new ThroughStream();

    $read = function () use (&$read, $stream, &$offset, $file): void {
        $file->getContents($offset, READ_CHUNK_SIZE)->then(
            function (string $contents) use (&$read, $stream, &$offset, $file): void {
                $length = strlen($contents);
                if ($length === 0) {
                    $stream->end('');
                    return;
                }
                $offset += $length;
                $stream->write($contents);
                $read();
            },
            function (Throwable $throwable) use ($stream) {
                $stream->emit('error', $throwable);
                $stream->close();
            }
        );
    };

    $read();

    return $stream;
}

Factory::create()->detect(__FILE__)->then(function (FileInterface $file) {
    $stream = streamFile($file);
    $stream->on('data', function (string $contents): void {
        echo $contents;
    });
    $stream->on('error', function (Throwable $throwable): void {
        echo $throwable;
    });
})->done();
