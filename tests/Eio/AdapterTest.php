<?php

namespace React\Tests\Filesystem\Eio;

use React\EventLoop\Factory;
use React\Filesystem\PermissionFlagResolver;
use React\Filesystem\Eio\Adapter;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;
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

    public function testGetSetFilesystem()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $filesystem = new Adapter($loop, [
            'pool' => [
                'class' => 'WyriHaximus\React\ChildProcess\Pool\Pool\Dummy',
            ],
        ]);

        $this->assertNull($filesystem->getFilesystem());
        $fs = \React\Filesystem\Filesystem::createFromAdapter($this->mockAdapter());
        $filesystem->setFilesystem($fs);

        $this->assertSame($fs, $filesystem->getFilesystem());
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

    public function testGetContents()
    {
        $loop = \React\EventLoop\Factory::create();
        $adapter = new Adapter($loop);

        $contents = $this->await($adapter->getContents(__FILE__), $loop);
        $this->assertSame(file_get_contents(__FILE__), $contents);
    }

    public function testGetContentsMinMax()
    {
        $loop = \React\EventLoop\Factory::create();
        $adapter = new Adapter($loop);

        $contents = $this->await($adapter->getContents(__FILE__, 5, 10), $loop);
        $this->assertSame(file_get_contents(__FILE__, false, null, 5, 10), $contents);
    }

    public function testPutContents()
    {
        $loop = \React\EventLoop\Factory::create();
        $adapter = new Adapter($loop);

        $tempFile = $this->tmpDir . uniqid('', true);
        $contents = sha1_file(__FILE__);

        $this->await($adapter->putContents($tempFile, $contents), $loop);
        $this->assertSame($contents, file_get_contents($tempFile));
    }

    public function testAppendContents()
    {
        $loop = \React\EventLoop\Factory::create();
        $adapter = new Adapter($loop);

        $tempFile = $this->tmpDir . uniqid('', true);
        $contents = sha1_file(__FILE__);

        file_put_contents($tempFile, $contents);
        $time = sha1(time());
        $contents .= $time;

        $this->await($adapter->appendContents($tempFile, $time), $loop);
        $this->assertSame($contents, file_get_contents($tempFile));
    }
}
