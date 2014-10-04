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

        $lsPromise = $this->getMock('React\Promise\PromiseInterface');


        $filesystem
            ->expects($this->once())
            ->method('ls')
            ->with($path)
            ->will($this->returnValue($lsPromise))
        ;

        $directory = new Directory($path, $filesystem);
        $this->assertInstanceOf('React\Promise\PromiseInterface', $directory->ls());
    }

    public function testLsSuccessAndProcessLsContents()
    {
        $dents = [
            'dents' => [
                [
                    'type' => EIO_DT_DIR,
                    'name' => 'bar',
                ],
                [
                    'type' => EIO_DT_REG,
                    'name' => 'foo',
                ],
            ],
        ];
        $path = '/home/foo/bar';
        $loop = $this->getMock('React\EventLoop\StreamSelectLoop', [
            'futureTick',
        ]);

        $loop
            ->expects($this->once())
            ->method('futureTick')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function($callback) use ($dents) {
                $callback($dents);
            }))
        ;

        $filesystem = $this->getMock('React\Filesystem\EioFilesystem', [
            'ls',
            'getLoop',
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

        $filesystem
            ->expects($this->once())
            ->method('getLoop')
            ->with()
            ->will($this->returnValue($loop))
        ;

        $lsPromise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'), $this->isType('callable'))
            ->will($this->returnCallback(function($resolveCb) use ($dents) {
                $resolveCb($dents);
            }))
        ;

        $directory = new Directory($path, $filesystem);
        $resultPromise = $directory->ls();
        $this->assertInstanceOf('React\Promise\PromiseInterface', $resultPromise);
        $callbackRan = false;
        $resultPromise->then(function($list) use (&$callbackRan) {
            $this->assertInternalType('array', $list);
            $this->assertInstanceOf('React\Filesystem\Node\Directory', $list['bar']);
            $this->assertInstanceOf('React\Filesystem\Node\File', $list['foo']);
            $callbackRan = true;
        });
        $this->assertTrue($callbackRan);
    }

    public function testLsFail()
    {
        $error = new \Exception('foor:bar');
        $path = '/home/foo/bar';$filesystem = $this->getMock('React\Filesystem\EioFilesystem', [
            'ls',
            'getLoop',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
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

        $lsPromise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'), $this->isType('callable'))
            ->will($this->returnCallback(function($resolveCb, $rejectCb) use ($error) {
                $rejectCb($error);
            }))
        ;

        $directory = new Directory($path, $filesystem);
        $resultPromise = $directory->ls();
        $this->assertInstanceOf('React\Promise\PromiseInterface', $resultPromise);
        $callbackRan = false;
        $resultPromise->then(null, function($passedError) use (&$callbackRan, $error) {
            $this->assertInstanceOf('Exception', $passedError);
            $this->assertSame($error, $passedError);
            $callbackRan = true;
        });
        $this->assertTrue($callbackRan);
    }

    public function testCreate()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioFilesystem', [
            'mkdir',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $promise = $this->getMock('React\Promise\PromiseInterface');

        $filesystem
            ->expects($this->once())
            ->method('mkdir')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $this->assertSame($promise, (new Directory($path, $filesystem))->create());
    }

    public function testRemove()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioFilesystem', [
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
}
