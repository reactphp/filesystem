<?php

namespace React\Tests\Filesystem\Filesystem;

use React\Filesystem\Filesystem\EioFilesystem;

class EioFilesystemTest extends \PHPUnit_Framework_TestCase {

    public function testEioExtensionInstalled() {
        $this->assertTrue(function_exists('eio_init'));
    }

    public function testInterface() {
        $this->assertInstanceOf('React\Filesystem\Filesystem\FilesystemInterface', new EioFilesystem($this->getMock('React\EventLoop\LoopInterface')));
    }

}
