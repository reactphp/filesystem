<?php


require dirname(__DIR__) . '/vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$filesystem = \React\Filesystem\Filesystem::create($loop);
$from = $filesystem->file(__FILE__);
$to = $filesystem->file(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'react_filesystem_file_to_file_copy_' . uniqid());

$from->copy($to);

$loop->run();
