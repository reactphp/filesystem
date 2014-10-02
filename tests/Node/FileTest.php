<?php

namespace React\Tests\Filesystem\Node;

use React\Filesystem\Node\File;

class FileTest extends \PHPUnit_Framework_TestCase
{

    public function testRemove()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioFilesystem', [
            'unlink',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $promise = $this->getMock('React\Promise\PromiseInterface');

        $filesystem
            ->expects($this->once())
            ->method('unlink')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $this->assertSame($promise, (new File($path, $filesystem))->remove());
    }

    public function testRename()
    {
        $pathFrom = 'foo.bar';
        $pathTo = 'bar.foo';
        $filesystem = $this->getMock('React\Filesystem\EioFilesystem', [
            'rename',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $promise = $this->getMock('React\Promise\PromiseInterface');

        $filesystem
            ->expects($this->once())
            ->method('rename')
            ->with($pathFrom, $pathTo)
            ->will($this->returnValue($promise))
        ;

        $this->assertSame($promise, (new File($pathFrom, $filesystem))->rename($pathTo));
    }

}
