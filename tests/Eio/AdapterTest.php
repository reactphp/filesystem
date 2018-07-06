<?php

namespace React\Tests\Filesystem\Eio;

use React\EventLoop\Factory;
use React\Filesystem\PermissionFlagResolver;
use React\Filesystem\Eio\Adapter;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;
use React\Tests\Filesystem\CallInvokerProvider;
use React\Tests\Filesystem\TestCase;

/**
 * @requires extension eio
 */
class AdapterTest extends TestCase
{
    public function testEioExtensionInstalled()
    {
        $this->assertTrue(function_exists('eio_init'));
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            'React\Filesystem\AdapterInterface',
            new Adapter($this->getMock('React\EventLoop\LoopInterface'))
        );
    }

    public function testGetLoop()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $filesystem = new Adapter($loop);
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
                    (new PermissionFlagResolver())->resolve(Adapter::CREATION_MODE),
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
        $promise = new FulfilledPromise();

        $loop = Factory::create();

        $filesystem = $this->getMock('React\Filesystem\Eio\Adapter', [
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

        $this->assertInstanceOf('React\Promise\ExtendedPromiseInterface', call_user_func_array([$filesystem, $externalMethod], $externalCallArgs));

        $loop->run();
    }

    public function testHandleEvent()
    {
        $filesystem = $this->getMock('React\Filesystem\Eio\Adapter', [
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
        $filesystem = $this->getMock('React\Filesystem\Eio\Adapter', [
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

    public function callInvokerProvider()
    {
        if (!extension_loaded('eio')) {
            return null;
        }
        $loop = Factory::create();
        $adapter = $this->getMock('React\Filesystem\Eio\Adapter', [
            'getLoop',
        ], [
            $loop,
        ]);

        $adapter
            ->expects($this->any())
            ->method('getLoop')
            ->will($this->returnValue($loop))
        ;

        return (new CallInvokerProvider())->callInvokerProvider($loop, $adapter);
    }

    /**
     * @dataProvider callInvokerProvider
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
            ->with('eio_lstat', [
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

        $this->assertInstanceOf('React\Promise\PromiseInterface', $adapter->touch($filename, Adapter::CREATION_MODE, $time));

        $loop->run();
    }

    /**
     * @dataProvider callInvokerProvider
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
            ->method('stat')
            ->with($filename)
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
     * @dataProvider callInvokerProvider
     */
    public function testTouchCreate($loop, $adapter, $invoker)
    {
        $filename = 'foo.bar';
        $fd = '01010100100010011110101';

        $adapter
            ->expects($this->at(1))
            ->method('stat')
            ->with($filename)
            ->will($this->returnValue(new RejectedPromise($fd)))
        ;

        $time = microtime(true);

        $adapter
            ->expects($this->at(2))
            ->method('callFilesystem')
            ->with('eio_open', [
                $filename,
                EIO_O_CREAT,
                (new PermissionFlagResolver())->resolve(Adapter::CREATION_MODE),
            ])
            ->will($this->returnValue(new FulfilledPromise($fd)))
        ;

        $adapter
            ->expects($this->at(2))
            ->method('close')
            ->with($fd)
            ->will($this->returnValue(new FulfilledPromise()))
        ;

        $this->assertInstanceOf('React\Promise\PromiseInterface', $adapter->touch($filename, Adapter::CREATION_MODE, $time));

        $loop->run();
    }

    /**
     * @dataProvider callInvokerProvider
     */
    public function _testOpen($loop, $adapter, $invoker)
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
                (new PermissionFlagResolver())->resolve(Adapter::CREATION_MODE),
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
        $loop = $this->getMock('React\EventLoop\LoopInterface', [
            'addReadStream',
            'addWriteStream',
            'removeReadStream',
            'removeWriteStream',
            'removeStream',
            'addTimer',
            'addPeriodicTimer',
            'cancelTimer',
            'isTimerActive',
            'nextTick',
            'futureTick',
            'tick',
            'run',
            'stop',
            'addSignal',
            'removeSignal',
        ]);

        $filesystem = new Adapter($loop);

        $loop
            ->expects($this->exactly(1))
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
        $loop = $this->getMock('React\EventLoop\LoopInterface', [
            'addReadStream',
            'addWriteStream',
            'removeReadStream',
            'removeWriteStream',
            'removeStream',
            'addTimer',
            'addPeriodicTimer',
            'cancelTimer',
            'isTimerActive',
            'nextTick',
            'futureTick',
            'tick',
            'run',
            'stop',
            'addSignal',
            'removeSignal',
        ]);

        $filesystem = new Adapter($loop);

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
        $loop = $this->getMock('React\EventLoop\LoopInterface', [
            'addReadStream',
            'addWriteStream',
            'removeReadStream',
            'removeWriteStream',
            'removeStream',
            'addTimer',
            'addPeriodicTimer',
            'cancelTimer',
            'isTimerActive',
            'nextTick',
            'futureTick',
            'tick',
            'run',
            'stop',
            'addSignal',
            'removeSignal',
        ]);

        $filesystem = new Adapter($loop);

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
        $loop = $this->getMock('React\EventLoop\LoopInterface', [
            'addReadStream',
            'addWriteStream',
            'removeReadStream',
            'removeWriteStream',
            'removeStream',
            'addTimer',
            'addPeriodicTimer',
            'cancelTimer',
            'isTimerActive',
            'nextTick',
            'futureTick',
            'tick',
            'run',
            'stop',
            'addSignal',
            'removeSignal',
        ]);

        $filesystem = $this->getMock('React\Filesystem\Eio\Adapter', [
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
        $loop = $this->getMock('React\EventLoop\LoopInterface', [
            'addReadStream',
            'addWriteStream',
            'removeReadStream',
            'removeWriteStream',
            'removeStream',
            'addTimer',
            'addPeriodicTimer',
            'cancelTimer',
            'isTimerActive',
            'nextTick',
            'futureTick',
            'tick',
            'run',
            'stop',
            'addSignal',
            'removeSignal',
        ]);

        $filesystem = $this->getMock('React\Filesystem\Eio\Adapter', [
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
        $this->assertInternalType('int', (new Adapter($this->getMock('React\EventLoop\LoopInterface')))->workPendingCount());
    }
}
