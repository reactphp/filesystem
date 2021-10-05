<?php

use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;
use React\Filesystem\Factory;
use React\Filesystem\Node\FileInterface;

require 'vendor/autoload.php';

$filename = tempnam(sys_get_temp_dir(), 'reactphp-filesystem-file-tail-example-');
$offset = 0;
$file = Factory::create()->detect($filename)->then(function (FileInterface $file) {
    Loop::addPeriodicTimer(1, function (TimerInterface $timer) use ($file, &$offset): void {
        $file->getContents($offset)->then(function (string $contents) use (&$offset, $timer): void {
            echo $contents;
            $offset += strlen($contents);
            if (trim($contents) === 'done') {
                Loop::cancelTimer($timer);
            }
        });
    });
})->done();

echo 'Append data to "', $filename, '" to see it appear beneath here, put "done" on a new line to stop watching it:', PHP_EOL;
