<?php

namespace React\Tests\Filesystem\Node;

use React\Filesystem\Node\Directory;

class DirectoryTest extends \PHPUnit_Framework_TestCase
{

    public function testLs()
    {
        $path = '/home/foo/bar';
        $loop = $this->getMock('React\EventLoop\StreamSelectLoop');

        $filesystem = $this->getMock('React\Filesystem\EioFilesystem', [
            'ls',
        ], [
            $loop,
        ]);

        $lsPromise = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);


        $filesystem
            ->expects($this->once())
            ->method('ls')
            ->with($path)
            ->will($this->returnValue($lsPromise))
        ;

        $directory = new Directory($path, $filesystem);
        $this->assertInstanceOf('React\Promise\PromiseInterface', $directory->ls());
    }

}
