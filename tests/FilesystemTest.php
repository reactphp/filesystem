<?php

namespace React\Tests\Filesystem;

use React\Filesystem\Filesystem;

class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $this->assertInstanceOf('React\Filesystem\Filesystem', Filesystem::create($this->getMock('React\EventLoop\StreamSelectLoop')));
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstruct()
    {
        $this->assertInstanceOf('React\Filesystem\Filesystem', new Filesystem());
    }

    public function testFile()
    {
        $this->assertInstanceOf('React\Filesystem\Node\File', Filesystem::create($this->getMock('React\EventLoop\StreamSelectLoop'))->file('foo.bar'));
    }

    public function testDir()
    {
        $this->assertInstanceOf('React\Filesystem\Node\Directory', Filesystem::create($this->getMock('React\EventLoop\StreamSelectLoop'))->dir('foo.bar'));
    }

    public function testGetContents()
    {
        $this->assertInstanceOf('React\Promise\PromiseInterface', Filesystem::create($this->getMock('React\EventLoop\StreamSelectLoop'))->getContents('foo.bar'));
    }
}
