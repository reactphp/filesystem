<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$filesystem = \React\Filesystem\Filesystem::create($loop);

$path = '/var/log/access.log';

$filesystem->getContents($path)->then(function ($content) use ($loop, $filesystem, $path) {
    echo $content;

    $lastSize = strlen($content);

    $file = $filesystem->file($path);

    $file->open('r')->then(function (\React\Stream\ReadableStreamInterface $stream) use ($filesystem, $loop, $file, &$lastSize) {
        /** @var \React\Filesystem\Stream\GenericStreamInterface $stream */
        $fileDescriptor = $stream->getFiledescriptor();

        $adapter = $filesystem->getAdapter();

        $loop->addPeriodicTimer(1, function () use ($adapter, $fileDescriptor, $file, &$lastSize) {
            $file->size()->then(function ($size) use ($adapter, $fileDescriptor, &$lastSize) {
                if ($lastSize === $size) {
                    return;
                }

                $adapter->read($fileDescriptor, $size - $lastSize, $lastSize)->then(function ($content) {
                    echo $content;
                });

                $lastSize = $size;
            });
        });
    });
});

$loop->run();
