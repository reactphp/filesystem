<?php

namespace React\Tests\Filesystem;

use React\Filesystem\EioFilesystem;

class EioFilesystemTest extends \PHPUnit_Framework_TestCase
{

    public function testEioExtensionInstalled()
    {
        $this->assertTrue(function_exists('eio_init'));
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            'React\Filesystem\FilesystemInterface',
            new EioFilesystem($this->getMock('React\EventLoop\LoopInterface'))
        );
    }

    public function testGetLoop()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $filesystem = new EioFilesystem($loop);
        $this->assertSame($loop, $filesystem->getLoop());
    }
}
