<?php

namespace React\Tests\Filesystem\Node;

use React\Filesystem\Node\Directory;
use React\Promise\FulfilledPromise;

class DirectoryTest extends \PHPUnit_Framework_TestCase
{
    use NodeTestTrait;

    protected function getNodeClass()
    {
        return 'React\Filesystem\Node\Directory';
    }

    public function testGetPath()
    {
        $path = 'foo.bar';
        $this->assertSame($path, (new Directory($path, $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ])))->getPath());
    }

    public function testLs()
    {
        $path = '/home/foo/bar';
        $loop = $this->getMock('React\EventLoop\StreamSelectLoop');

        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'ls',
        ], [
            $loop,
        ]);

        $lsStream = $this->getMock('React\Filesystem\ObjectStream');

        $filesystem
            ->expects($this->once())
            ->method('ls')
            ->with()
            ->will($this->returnValue($lsStream))
        ;

        $directory = new Directory($path, $filesystem);

        $this->assertInstanceOf('React\Promise\PromiseInterface', $directory->ls());
    }

    public function testCreate()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'mkdir',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $promise = new FulfilledPromise();

        $filesystem
            ->expects($this->once())
            ->method('mkdir')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $this->assertInstanceOf('React\Promise\PromiseInterface', (new Directory($path, $filesystem))->create());
    }

    public function testRemove()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'rmdir',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $promise = $this->getMock('React\Promise\PromiseInterface');

        $filesystem
            ->expects($this->once())
            ->method('rmdir')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $this->assertSame($promise, (new Directory($path, $filesystem))->remove());
    }
    public function testSize()
    {
        $path = '/home/foo/bar';
        $loop = $this->getMock('React\EventLoop\StreamSelectLoop');

        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $loop,
        ]);

        $lsPromise = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $lsPromise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($callback) {
                return $callback('foo.bar');
            }))
        ;

        $directory = $this->getMock('React\Filesystem\Node\Directory', [
            'ls',
            'processSizeContents',
        ], [
            $path,
            $filesystem,
        ]);

        $directory
            ->expects($this->once())
            ->method('ls')
            ->with()
            ->will($this->returnValue($lsPromise))
        ;

        $directory
            ->expects($this->once())
            ->method('processSizeContents')
            ->with('foo.bar', $this->isType('boolean'))
            ->will($this->returnValue($this->getMock('React\Promise\PromiseInterface')))
        ;

        $this->assertInstanceOf('React\Promise\PromiseInterface', $directory->size());
    }

    public function testChmodRecursive()
    {
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $recursiveInvoker = $this->getMock('React\Filesystem\Node\RecursiveInvoker', [
            'execute',
        ], [
            $this->getMock('React\Filesystem\Node\DirectoryInterface', [], [
                'foo/bar/',
                $filesystem,
            ]),
        ]);

        $promise = $this->getMock('React\Promise\PromiseInterface');

        $recursiveInvoker
            ->expects($this->once())
            ->method('execute')
            ->with('chmod', [123])
            ->will($this->returnValue($promise))
        ;

        $this->assertSame($promise, (new Directory('foo/bar/', $filesystem, $recursiveInvoker))->chmodRecursive(123));
    }

    public function testChownRecursive()
    {
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $recursiveInvoker = $this->getMock('React\Filesystem\Node\RecursiveInvoker', [
            'execute',
        ], [
            $this->getMock('React\Filesystem\Node\DirectoryInterface', [], [
                'foo/bar/',
                $filesystem,
            ]),
        ]);

        $promise = $this->getMock('React\Promise\PromiseInterface');

        $recursiveInvoker
            ->expects($this->once())
            ->method('execute')
            ->with('chown', [1, 2])
            ->will($this->returnValue($promise))
        ;

        $this->assertSame($promise, (new Directory('foo/bar/', $filesystem, $recursiveInvoker))->chownRecursive(1, 2));
    }

    public function testRemoveRecursive()
    {
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $recursiveInvoker = $this->getMock('React\Filesystem\Node\RecursiveInvoker', [
            'execute',
        ], [
            $this->getMock('React\Filesystem\Node\DirectoryInterface', [], [
                'foo/bar/',
                $filesystem,
            ]),
        ]);

        $promise = $this->getMock('React\Promise\PromiseInterface');

        $recursiveInvoker
            ->expects($this->once())
            ->method('execute')
            ->with('remove', [])
            ->will($this->returnValue($promise))
        ;

        $this->assertSame($promise, (new Directory('foo/bar/', $filesystem, $recursiveInvoker))->removeRecursive());
    }
}
