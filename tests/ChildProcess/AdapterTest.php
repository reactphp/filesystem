<?php

namespace React\Tests\Filesystem\ChildProcess;

use React\EventLoop\Factory;
use React\Filesystem\ChildProcess\Adapter;
use React\Filesystem\Filesystem;
use React\Filesystem\Node\NodeInterface;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;
use React\Tests\Filesystem\TestCase;

class AdapterTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            'React\Filesystem\AdapterInterface',
            new Adapter($this->getMock('React\EventLoop\LoopInterface'), [
                'pool' => [
                    'class' => 'WyriHaximus\React\ChildProcess\Pool\Pool\Dummy',
                ],
            ])
        );
    }

    public function testGetLoop()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $filesystem = new Adapter($loop, [
            'pool' => [
                'class' => 'WyriHaximus\React\ChildProcess\Pool\Pool\Dummy',
            ],
        ]);
        $this->assertSame($loop, $filesystem->getLoop());
    }

    public function callFilesystemProvider()
    {
        return [
            [
                'mkdir',
                [
                    'foo.bar',
                ],
                [
                    'mkdir',
                    [
                        'path' => 'foo.bar',
                        'mode' => 760,
                    ],
                ],
            ],
            [
                'mkdir',
                [
                    'foo.bar',
                    'rwxrwxrwx',
                ],
                [
                    'mkdir',
                    [
                        'path' => 'foo.bar',
                        'mode' => 777,
                    ],
                ],
            ],
            [
                'rmdir',
                [
                    'foo.bar',
                ],
                [
                    'rmdir',
                    [
                        'path' => 'foo.bar',
                    ],
                ],
            ],
            [
                'unlink',
                [
                    'foo.bar',
                ],
                [
                    'unlink',
                    [
                        'path' => 'foo.bar',
                    ],
                ],
            ],
            [
                'touch',
                [
                    'foo.bar',
                ],
                [
                    'touch',
                    [
                        'path' => 'foo.bar',
                        'mode' => 760,
                    ],
                ],
            ],
            [
                'rename',
                [
                    'foo.bar',
                    'bar.foo',
                ],
                [
                    'rename',
                    [
                        'from' => 'foo.bar',
                        'to' => 'bar.foo',
                    ],
                ],
            ],
            [
                'chown',
                [
                    'foo.bar',
                    0,
                    2,
                ],
                [
                    'chown',
                    [
                        'path' => 'foo.bar',
                        'uid' => 0,
                        'gid' => 2,
                    ],
                ],
            ],
            [
                'chmod',
                [
                    'foo.bar',
                    0123,
                ],
                [
                    'chmod',
                    [
                        'path' => 'foo.bar',
                        'mode' => 123,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider callFilesystemProvider
     */
    public function testCallFilesystem($method, $arguments, $mockArguments)
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $filesystem = new Adapter($loop, [
            'pool' => [
                'class' => 'WyriHaximus\React\ChildProcess\Pool\Pool\Dummy',
            ],
        ]);
        $invoker = $this->getMock('React\Filesystem\CallInvokerInterface', [
            '__construct',
            'invokeCall',
            'isEmpty',
        ]);
        $filesystem->setInvoker($invoker);

        $promise = new FulfilledPromise();

        call_user_func_array([
            $invoker
                ->expects($this->once())
                ->method('invokeCall')
            ,
            'with',
        ], $mockArguments)->will($this->returnValue($promise));

        $this->assertSame($promise, call_user_func_array([$filesystem, $method], $arguments));
    }

    public function testReadlink()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $filesystem = new Adapter($loop, [
            'pool' => [
                'class' => 'WyriHaximus\React\ChildProcess\Pool\Pool\Dummy',
            ],
        ]);
        $invoker = $this->getMock('React\Filesystem\CallInvokerInterface', [
            '__construct',
            'invokeCall',
            'isEmpty',
        ]);
        $filesystem->setInvoker($invoker);

        $invoker
            ->expects($this->once())
            ->method('invokeCall')
            ->with(
                'readlink',
                [
                    'path' => 'foo.bar',
                ]
            )->will($this->returnValue(new FulfilledPromise([
                'path' => 'bar.foo',
            ])))
        ;

        $filesystem->readlink('foo.bar');
    }

    public function testStat()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $filesystem = new Adapter($loop, [
            'pool' => [
                'class' => 'WyriHaximus\React\ChildProcess\Pool\Pool\Dummy',
            ],
        ]);
        $invoker = $this->getMock('React\Filesystem\CallInvokerInterface', [
            '__construct',
            'invokeCall',
            'isEmpty',
        ]);
        $filesystem->setInvoker($invoker);

        $time = time();
        $invoker
            ->expects($this->once())
            ->method('invokeCall')
            ->with(
                'stat',
                [
                    'path' => 'foo.bar',
                ]
            )->will($this->returnValue(new FulfilledPromise([
                'atime' => $time,
                'mtime' => $time,
                'ctime' => $time,
            ])))
        ;

        $filesystem->stat('foo.bar');
    }

    public function testSymlink()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $filesystem = new Adapter($loop, [
            'pool' => [
                'class' => 'WyriHaximus\React\ChildProcess\Pool\Pool\Dummy',
            ],
        ]);
        $invoker = $this->getMock('React\Filesystem\CallInvokerInterface', [
            '__construct',
            'invokeCall',
            'isEmpty',
        ]);
        $filesystem->setInvoker($invoker);

        $invoker
            ->expects($this->once())
            ->method('invokeCall')
            ->with(
                'symlink',
                [
                    'from' => 'foo.bar',
                    'to' => 'bar.foo',
                ]
            )->will($this->returnValue(new FulfilledPromise([
                'result' => true,
            ])))
        ;

        $filesystem->symlink('foo.bar', 'bar.foo');
    }

    public function testLs()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $adapter = new Adapter($loop, [
            'pool' => [
                'class' => 'WyriHaximus\React\ChildProcess\Pool\Pool\Dummy',
            ],
        ]);
        $invoker = $this->getMock('React\Filesystem\CallInvokerInterface', [
            '__construct',
            'invokeCall',
            'isEmpty',
        ]);
        $adapter->setInvoker($invoker);
        Filesystem::createFromAdapter($adapter);

        $deferred = new Deferred();

        $invoker
            ->expects($this->once())
            ->method('invokeCall')
            ->with(
                'readdir',
                [
                    'path' => 'foo.bar',
                    'flags' => 2,
                ]
            )->will($this->returnValue($deferred->promise()))
        ;

        $stream = $adapter->ls('foo.bar');
        $this->assertInstanceOf('React\Filesystem\ObjectStream', $stream);

        $calledOnData = false;
        $stream->on('data', function (NodeInterface $file) use (&$calledOnData) {
            $this->assertInstanceOf('React\Filesystem\Node\File', $file);
            $this->assertSame('foo.bar/bar.foo', $file->getPath());
            $calledOnData = true;
        });

        $deferred->resolve([
            [
                'type' => 'file',
                'name' => 'bar.foo',
            ],
        ]);
        $this->assertTrue($calledOnData);
    }

    public function testErrorFromPool()
    {
        $this->setExpectedException('\Exception', 'oops');

        $loop = Factory::create();
        $adapter = new Adapter($loop, [
            'pool' => [
                'class' => 'React\Tests\Filesystem\ChildProcess\PoolRpcErrorMockFactory',
            ],
        ]);
        $this->await($adapter->touch('foo.bar'), $loop, 1);
    }
}
