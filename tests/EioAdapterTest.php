<?php

namespace React\Tests\Filesystem;

use React\EventLoop\Factory;
use React\Filesystem\Eio\PermissionFlagResolver;
use React\Filesystem\EioAdapter;
use React\Promise\FulfilledPromise;

class EioAdapterTest extends \PHPUnit_Framework_TestCase
{

    public function testEioExtensionInstalled()
    {
        $this->assertTrue(function_exists('eio_init'));
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            'React\Filesystem\AdapterInterface',
            new EioAdapter($this->getMock('React\EventLoop\LoopInterface'))
        );
    }

    public function testGetLoop()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $filesystem = new EioAdapter($loop);
        $this->assertSame($loop, $filesystem->getLoop());
    }

    public function testCallFilesystemCallsProvider()
    {
        $pathName = 'foo.bar';
        return [
            [
                'unlink',
                'eio_unlink',
                [
                    $pathName,
                ],
                [
                    $pathName,
                ],
            ],
            [
                'rename',
                'eio_rename',
                [
                    $pathName,
                    str_rot13($pathName),
                ],
                [
                    $pathName,
                    str_rot13($pathName),
                ],
            ],
            [
                'chmod',
                'eio_chmod',
                [
                    $pathName,
                    123,
                ],
                [
                    $pathName,
                    123,
                ],
            ],
            [
                'chown',
                'eio_chown',
                [
                    $pathName,
                    1,
                    2,
                ],
                [
                    $pathName,
                    1,
                    2,
                ],
            ],
            [
                'mkdir',
                'eio_mkdir',
                [
                    $pathName,
                ],
                [
                    $pathName,
                    (new PermissionFlagResolver())->resolve(EioAdapter::CREATION_MODE),
                ],
            ],
            [
                'mkdir',
                'eio_mkdir',
                [
                    $pathName,
                    'rwxrwxrwx',
                ],
                [
                    $pathName,
                    (new PermissionFlagResolver())->resolve('rwxrwxrwx'),
                ],
            ],
            [
                'rmdir',
                'eio_rmdir',
                [
                    $pathName,
                ],
                [
                    $pathName,
                ],
            ],
            [
                'close',
                'eio_close',
                [
                    $pathName,
                ],
                [
                    $pathName,
                ],
            ],
            [
                'read',
                'eio_read',
                [
                    $pathName,
                    123,
                    456,
                ],
                [
                    $pathName,
                    123,
                    456,
                ],
            ],
            [
                'write',
                'eio_write',
                [
                    $pathName,
                    'abc',
                    3,
                    456,
                ],
                [
                    $pathName,
                    'abc',
                    3,
                    456,
                ],
            ],
        ];
    }

    /**
     * @dataProvider testCallFilesystemCallsProvider
     */
    public function testCallFilesystemCalls($externalMethod, $internalMethod, $externalCallArgs, $internalCallArgs, $errorResultCode = -1)
    {
        $promise = $this->getMock('React\Promise\PromiseInterface');

        $loop = Factory::create();

        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'callFilesystem',
        ], [
            $loop,
        ]);

        $filesystem
            ->expects($this->once())
            ->method('callFilesystem')
            ->with($internalMethod, $internalCallArgs, $errorResultCode)
            ->will($this->returnValue($promise))
        ;

        $this->assertInstanceOf('React\Promise\PromiseInterface', call_user_func_array([$filesystem, $externalMethod], $externalCallArgs));

        $loop->run();
    }

    public function testHandleEvent()
    {
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'workPendingCount',
            'unregister',
        ], [
            $this->getMock('React\EventLoop\LoopInterface'),
        ]);

        $filesystem
            ->expects($this->once())
            ->method('unregister')
            ->with()
        ;

        $filesystem
            ->expects($this->at(0))
            ->method('workPendingCount')
            ->with()
            ->will($this->returnValue(1))
        ;

        $filesystem
            ->expects($this->at(1))
            ->method('workPendingCount')
            ->with()
            ->will($this->returnValue(0))
        ;

        $filesystem->handleEvent();
    }

    public function testHandleEventNothingToDo()
    {
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'workPendingCount',
            'unregister',
        ], [
            $this->getMock('React\EventLoop\LoopInterface'),
        ]);

        $filesystem
            ->expects($this->at(0))
            ->method('workPendingCount')
            ->with()
            ->will($this->returnValue(0))
        ;

        $filesystem
            ->expects($this->never())
            ->method('unregister')
            ->with()
        ;

        $filesystem->handleEvent();
    }

    /**
     * @dataProvider React\Tests\Filesystem\CallInvokerProvider::callInvokerProvider
     */
    public function testTouchExists($loop, $adapter, $invoker)
    {
        $filename = 'foo.bar';
        $fd = '01010100100010011110101';

        $promise = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $promise
            ->expects($this->any())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) use ($fd) {
                return $resolveCb($fd);
            }))
        ;

        $adapter
            ->expects($this->at(0))
            ->method('callFilesystem')
            ->with('eio_stat', [
                $filename,
            ])
            ->will($this->returnValue($promise))
        ;

        $time = microtime(true);

        $adapter
            ->expects($this->at(1))
            ->method('callFilesystem')
            ->with('eio_utime', [
                $filename,
                $time,
                $time,
            ])
            ->will($this->returnValue($promise))
        ;

        $this->assertInstanceOf('React\Promise\PromiseInterface', $adapter->touch($filename, EioAdapter::CREATION_MODE, $time));

        $loop->run();
    }

    /**
     * @dataProvider React\Tests\Filesystem\CallInvokerProvider::callInvokerProvider
     */
    public function testTouchExistsNoTime($loop, $adapter, $invoker)
    {
        $filename = 'foo.bar';
        $fd = '01010100100010011110101';

        $promise = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $promise
            ->expects($this->any())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) use ($fd) {
                return $resolveCb($fd);
            }, function ($resolveCb) use ($fd) {
                return $resolveCb($fd);
            }))
        ;

        $adapter
            ->expects($this->at(0))
            ->method('callFilesystem')
            ->with('eio_stat', [
                $filename,
            ])
            ->will($this->returnValue($promise))
        ;

        $adapter
            ->expects($this->at(1))
            ->method('callFilesystem')
            ->with('eio_utime', $this->callback(function ($array) use ($filename) {
                return $array[0] === $filename && is_float($array[1]) && is_float($array[2]);
            }))
            ->will($this->returnValue($promise))
        ;

        $this->assertInstanceOf('React\Promise\PromiseInterface', $adapter->touch($filename));

        $loop->run();
    }

    /**
     * @dataProvider React\Tests\Filesystem\CallInvokerProvider::callInvokerProvider
     */
    public function testTouchCreate($loop, $adapter, $invoker)
    {
        $filename = 'foo.bar';
        $fd = '01010100100010011110101';

        $promiseA = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $promiseA
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($void, $resolveCb) use ($fd) {
                return $resolveCb($fd);
            }))
        ;

        $promiseB = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $promiseB
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) use ($fd) {
                return $resolveCb($fd);
            }))
        ;

        $adapter
            ->expects($this->at(0))
            ->method('callFilesystem')
            ->with('eio_stat', [
                $filename,
            ])
            ->will($this->returnValue($promiseA))
        ;

        $time = microtime(true);

        $adapter
            ->expects($this->at(1))
            ->method('callFilesystem')
            ->with('eio_open', [
                $filename,
                EIO_O_CREAT,
                (new PermissionFlagResolver())->resolve(EioAdapter::CREATION_MODE),
            ])
            ->will($this->returnValue($promiseB))
        ;

        $adapter
            ->expects($this->at(2))
            ->method('close')
            ->with($fd)
            ->will($this->returnValue(new FulfilledPromise()))
        ;

        $this->assertInstanceOf('React\Promise\PromiseInterface', $adapter->touch($filename, EioAdapter::CREATION_MODE, $time));

        $loop->run();
    }

    /**
     * @dataProvider React\Tests\Filesystem\CallInvokerProvider::callInvokerProvider
     */
    public function testOpen($loop, $adapter, $invoker)
    {
        $filename = 'foo.bar';
        $fd = '01010100100010011110101';

        $promise = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $promise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) use ($fd) {
                return new FulfilledPromise($resolveCb($fd));
            }))
        ;

        $adapter
            ->expects($this->at(0))
            ->method('callFilesystem')
            ->with('eio_open', [
                $filename,
                2,
                (new PermissionFlagResolver())->resolve(EioAdapter::CREATION_MODE),
            ])
            ->will($this->returnValue($promise))
        ;

        $adapter->open($filename, '+')->then(function ($stream) {
            $this->assertInstanceOf('React\Filesystem\Eio\DuplexStream', $stream);
        });

        $loop->run();
    }

    public function testExecuteDelayedCall()
    {
        $loop = $this->getMock('React\EventLoop\StreamSelectLoop', [
            'futureTick',
            'addReadStream',
        ]);

        $filesystem = new EioAdapter($loop);

        $loop
            ->expects($this->exactly(2))
            ->method('futureTick')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) {
                $resolveCb();
            }))
        ;

        $loop
            ->expects($this->once())
            ->method('addReadStream')
            ->with($this->isType('resource'), [
                $filesystem,
                'handleEvent',
            ])
        ;

        $calledFunction = false;
        $calledCallback = false;
        $filesystem->callFilesystem(function ($priority, $callback) use (&$calledFunction) {
            $this->assertSame(EIO_PRI_DEFAULT, $priority);
            $callback('', 0, 0);
            $calledFunction = true;
            return true;
        }, [])->then(function () use (&$calledCallback) {
            $calledCallback = true;
        });
        $filesystem->callFilesystem(function () {
            return true;
        }, []);

        $this->assertTrue($calledFunction);
        $this->assertTrue($calledCallback);
    }

    public function testExecuteDelayedCallFailed()
    {
        $loop = $this->getMock('React\EventLoop\StreamSelectLoop', [
            'futureTick',
            'addReadStream',
        ]);

        $filesystem = new EioAdapter($loop);

        $loop
            ->expects($this->once())
            ->method('futureTick')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) {
                $resolveCb();
            }))
        ;

        $loop
            ->expects($this->once())
            ->method('addReadStream')
            ->with($this->isType('resource'), [
                $filesystem,
                'handleEvent',
            ])
        ;

        $calledFunction = false;
        $calledCallback = false;
        $filesystem->callFilesystem(function ($priority, $callback) use (&$calledFunction) {
            $this->assertSame(EIO_PRI_DEFAULT, $priority);
            $callback('', -1, 0);
            $calledFunction = true;
            return true;
        }, [])->then(null, function ($e) use (&$calledCallback) {
            $this->assertInstanceOf('UnexpectedValueException', $e);
            $calledCallback = true;
        });

        $this->assertTrue($calledFunction);
        $this->assertTrue($calledCallback);
    }

    public function testExecuteDelayedCallFailedResult()
    {
        $loop = $this->getMock('React\EventLoop\StreamSelectLoop', [
            'futureTick',
            'addReadStream',
        ]);

        $filesystem = new EioAdapter($loop);

        $loop
            ->expects($this->once())
            ->method('futureTick')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) {
                $resolveCb();
            }))
        ;

        $loop
            ->expects($this->once())
            ->method('addReadStream')
            ->with($this->isType('resource'), [
                $filesystem,
                'handleEvent',
            ])
        ;

        $calledFunction = false;
        $calledCallback = false;
        $filesystem->callFilesystem(function ($priority, $callback) use (&$calledFunction) {
            $this->assertSame(EIO_PRI_DEFAULT, $priority);
            $callback('', -1, 0);
            $calledFunction = true;
            return false;
        }, [])->then(null, function ($e) use (&$calledCallback) {
            $this->assertInstanceOf('UnexpectedValueException', $e);
            $calledCallback = true;
        });

        $this->assertTrue($calledFunction);
        $this->assertTrue($calledCallback);
    }

    public function testUnregister()
    {
        $loop = $this->getMock('React\EventLoop\StreamSelectLoop', [
            'futureTick',
            'removeReadStream',
        ]);

        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'workPendingCount',
        ], [
            $loop,
        ]);

        $filesystem
            ->expects($this->at(0))
            ->method('workPendingCount')
            ->with()
            ->will($this->returnValue(1))
        ;

        $filesystem
            ->expects($this->at(1))
            ->method('workPendingCount')
            ->with()
            ->will($this->returnValue(0))
        ;

        $loop
            ->expects($this->once())
            ->method('futureTick')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) {
                $resolveCb();
            }))
        ;

        $loop
            ->expects($this->once())
            ->method('removeReadStream')
            ->with($this->isType('resource'), [
            $filesystem,
            'handleEvent',
            ])
            ->will($this->returnValue(1))
        ;

        $filesystem->callFilesystem(function () {
            return true;
        }, []);

        $filesystem->handleEvent();
    }

    public function testUnregisterInactive()
    {
        $loop = $this->getMock('React\EventLoop\StreamSelectLoop', [
            'removeReadStream',
        ]);

        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'workPendingCount',
        ], [
            $loop,
        ]);

        $filesystem
            ->expects($this->at(0))
            ->method('workPendingCount')
            ->with()
            ->will($this->returnValue(1))
        ;

        $filesystem
            ->expects($this->at(1))
            ->method('workPendingCount')
            ->with()
            ->will($this->returnValue(0))
        ;

        $loop
            ->expects($this->never())
            ->method('removeReadStream')
            ->with($this->isType('resource'), [
                $filesystem,
                'handleData',
            ])
            ->will($this->returnValue(1))
        ;

        $filesystem->handleEvent();
    }

    public function testWorkPendingCount()
    {
        $this->assertInternalType('int', (new EioAdapter($this->getMock('React\EventLoop\LoopInterface')))->workPendingCount());
    }
}
