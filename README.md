ReactFilesystem
===============

React WIP for EIO

## Poc Demo ##

Run with: clear && touch test_rm && time php test.php

```php
<?php

require 'vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();
$filesystem = new \React\Filesystem\Filesystem($loop);
$loop->addTimer(1, function() use ($filesystem) {
    $filesystem->dir(__DIR__)->ls()->then(function ($a) {
        var_export($a);
    });
});
$loop->addTimer(0.0001, function() use ($filesystem) {
    $file = $filesystem->file(__FILE__);
    $file->exists()->then(function() use ($file) {
        echo __FILE__ . ' exists', PHP_EOL;
        $file->size()->then(function($size) use ($file) {
            var_export($size);
            echo PHP_EOL;
            $file->time()->then(function($time) {
                var_export($time);
                echo PHP_EOL;
            });
        });
    });
    $rm = $filesystem->file('./test_rm');
    $rm->exists()->then(function() use ($rm) {
        $rm->stat()->then(function($result) use ($rm) {
            var_export($result);
            echo PHP_EOL;
            $rm->chmod(0777)->then(function() use ($rm) {
                $rm->stat()->then(function($result) use ($rm) {
                    var_export($result);
                    echo PHP_EOL;
                    $rm->remove()->then(function() {
                        echo 'test_rm removed', PHP_EOL;
                    }, function() {
                        echo 'test_rm note removed', PHP_EOL;
                    });
                });
            });
        });
    });
});

$loop->run();
```