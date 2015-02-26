<?php

namespace React\Tests\Filesystem;

use React\Filesystem\Filesystem;

class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $this->assertInstanceOf(
            'React\Filesystem\Filesystem',
            Filesystem::create($this->getMock('React\EventLoop\StreamSelectLoop'))
        );
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testFactory()
    {
        $this->assertInstanceOf('React\Filesystem\Filesystem', Filesystem::create());
    }

    public function testFile()
    {
        $file = Filesystem::create($this->getMock('React\EventLoop\StreamSelectLoop'))->file('foo.bar');
        $this->assertInstanceOf('React\Filesystem\Node\File', $file);
        $this->assertInstanceOf('React\Filesystem\Node\GenericOperationInterface', $file);
    }

    public function testDir()
    {
        $directory = Filesystem::create($this->getMock('React\EventLoop\StreamSelectLoop'))->dir('foo.bar');
        $this->assertInstanceOf('React\Filesystem\Node\Directory', $directory);
        $this->assertInstanceOf('React\Filesystem\Node\GenericOperationInterface', $directory);
    }

    public function testGetContents()
    {
        $this->assertInstanceOf(
            'React\Promise\PromiseInterface',
            Filesystem::create($this->getMock('React\EventLoop\StreamSelectLoop'))->getContents('foo.bar')
        );
    }
}
