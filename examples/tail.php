<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$filesystem = \React\Filesystem\Filesystem::create($loop);

$path = '/var/log/access.log';

$filesystem->getContents($path)->then(function ($content) use ($loop, $filesystem, $path) {
    echo $content;

    $lastSize = strlen($content);
    $adapter = $filesystem->getAdapter();

    $adapter->open($path, 'r')->then(function ($fileDescriptor) use ($adapter, $filesystem, $loop, $path, &$lastSize) {
        $file = $filesystem->file($path);

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
