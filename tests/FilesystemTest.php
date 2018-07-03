<?php

namespace React\Tests\Filesystem;

use React\Filesystem\Filesystem;
use React\Filesystem\InstantInvoker;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;

class FilesystemTest extends TestCase
{
    public function testCreate()
    {
        $this->assertInstanceOf(
            'React\Filesystem\Filesystem',
            Filesystem::create($this->getMock('React\EventLoop\LoopInterface'), [
                'pool' => [
                    'class' => 'WyriHaximus\React\ChildProcess\Pool\Pool\Dummy',
                ],
            ])
        );
    }
    public function testCreateWithAdapter()
    {
        $this->assertInstanceOf(
            'React\Filesystem\Filesystem',
            Filesystem::createFromAdapter($this->mockAdapter())
        );
    }

    public function testFactory()
    {
        try {
            $this->assertInstanceOf('React\Filesystem\Filesystem', Filesystem::create(null, [
                'pool' => [
                    'class' => 'WyriHaximus\React\ChildProcess\Pool\Pool\Dummy',
                ],
            ]));
        } catch (\PHPUnit_Framework_Error $typeError) {
            $this->assertTrue(true);
        } catch (\TypeError $typeError) {
            $this->assertTrue(true);
        }
    }

    public function testFile()
    {
        $file = Filesystem::create($this->getMock('React\EventLoop\LoopInterface'), [
            'pool' => [
                'class' => 'WyriHaximus\React\ChildProcess\Pool\Pool\Dummy',
            ],
        ])->file('foo.bar');
        $this->assertInstanceOf('React\Filesystem\Node\File', $file);
        $this->assertInstanceOf('React\Filesystem\Node\GenericOperationInterface', $file);
    }

    public function testDir()
    {
        $directory = Filesystem::create($this->getMock('React\EventLoop\LoopInterface'), [
            'pool' => [
                'class' => 'WyriHaximus\React\ChildProcess\Pool\Pool\Dummy',
            ],
        ])->dir('foo.bar');
        $this->assertInstanceOf('React\Filesystem\Node\Directory', $directory);
        $this->assertInstanceOf('React\Filesystem\Node\GenericOperationInterface', $directory);
    }

    public function testGetContents()
    {
        $adapter = $this->mockAdapter();
        $adapter
            ->expects($this->any())
            ->method('stat')
            ->will($this->returnValue(new FulfilledPromise([])))
        ;
        $adapter
            ->expects($this->any())
            ->method('open')
            ->will($this->returnValue(new RejectedPromise()))
        ;
        $this->assertInstanceOf(
            'React\Promise\PromiseInterface',
            Filesystem::createFromAdapter($adapter)->getContents('foo.bar')
        );
    }

    public function testSetFilesystemAndInvoker()
    {
        $adapter = $this->mockAdapter();
        $invoker = new InstantInvoker($adapter);
        $adapter
            ->expects($this->at(0))
            ->method('setFilesystem')
            ->with($this->isInstanceOf('React\Filesystem\Filesystem'))
        ;
        $adapter
            ->expects($this->at(1))
            ->method('setInvoker')
            ->with($invoker)
        ;
        Filesystem::createFromAdapter($adapter)->setInvoker($invoker);
    }
}
