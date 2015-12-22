<?php

namespace React\Tests\Filesystem\ChildProcess;

use React\Filesystem\ChildProcess\Adapter;
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
                    'class' => 'WyriHaximus\React\ChildProcess\Pool\DummyPool',
                ],
            ])
        );
    }

    public function testGetLoop()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $filesystem = new Adapter($loop, [
            'pool' => [
                'class' => 'WyriHaximus\React\ChildProcess\Pool\DummyPool',
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
                        'mode' => Adapter::CREATION_MODE,
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
                        'mode' => 'rwxrwxrwx',
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
                    123,
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
                'class' => 'WyriHaximus\React\ChildProcess\Pool\DummyPool',
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
                'class' => 'WyriHaximus\React\ChildProcess\Pool\DummyPool',
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
                'class' => 'WyriHaximus\React\ChildProcess\Pool\DummyPool',
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
}
