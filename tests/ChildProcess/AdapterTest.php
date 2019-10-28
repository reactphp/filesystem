<?php

namespace React\Tests\Filesystem\ChildProcess;

use React\EventLoop\Factory;
use React\Filesystem\ChildProcess\Adapter;
use React\Filesystem\Filesystem;
use React\Filesystem\Node\File;
use React\Filesystem\Node\NodeInterface;
use React\Promise\Deferred;
use React\Tests\Filesystem\TestCase;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Payload;

class AdapterTest extends TestCase
{
    public function tearDown()
    {
        SingletonPoolStub::reset();
    }

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
                        'mode' => '760',
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
                        'mode' => '777',
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
                        'mode' => '760',
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
                        'mode' => '123',
                    ],
                ],
            ],
            [
                'readlink',
                [
                    'foo.bar',
                ],
                [
                    'readlink',
                    [
                        'path' => 'foo.bar',
                    ],
                ],
            ],
            [
                'stat',
                [
                    'foo.bar',
                ],
                [
                    'stat',
                    [
                        'path' => 'foo.bar',
                    ],
                ],
            ],
            [
                'symlink',
                [
                    'foo.bar',
                    'bar.foo',
                ],
                [
                    'symlink',
                    [
                        'from' => 'foo.bar',
                        'to' => 'bar.foo',
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
                'class' => 'React\Tests\Filesystem\ChildProcess\SingletonPoolStub',
            ],
        ]);

        call_user_func_array([$filesystem, $method], $arguments);

        $calls = SingletonPoolStub::getCalls();
        self::assertCount(1, $calls);
        /** @var array $call */
        $call = $calls[0][1][0]->jsonSerialize();
        self::assertSame($mockArguments[0], $call['target']);
        self::assertSame($mockArguments[1], $call['payload']->getPayload());
    }

    public function testLs()
    {
        $loop = \React\EventLoop\Factory::create();
        $adapter = new Adapter($loop, [
            'pool' => [
                'class' => 'React\Tests\Filesystem\ChildProcess\SingletonPoolStub',
            ],
        ]);

        $deferred = new Deferred();
        SingletonPoolStub::setRpcResponse($deferred->promise());

        $fs = Filesystem::createFromAdapter($adapter);

        $promise = $adapter->ls('foo.bar');
        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);
        $deferred->resolve(new Payload([
            [
                'type' => 'file',
                'name' => 'bar.foo',
            ],
        ]));

        $nodes = $this->await($promise, $loop);

        $calls = SingletonPoolStub::getCalls();
        self::assertCount(1, $calls);
        /** @var array $call */
        $call = $calls[0][1][0]->jsonSerialize();
        self::assertSame('readdir', $call['target']);
        self::assertSame([
            'path' => 'foo.bar',
            'flags' => 2,
        ], $call['payload']->getPayload());

        $this->assertEquals(new File('foo.bar/bar.foo', $fs), reset($nodes));
    }

    public function testLsStream()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $adapter = new Adapter($loop, [
            'pool' => [
                'class' => 'React\Tests\Filesystem\ChildProcess\SingletonPoolStub',
            ],
        ]);

        $deferred = new Deferred();
        SingletonPoolStub::setRpcResponse($deferred->promise());

        Filesystem::createFromAdapter($adapter);

        $stream = $adapter->lsStream('foo.bar');
        $this->assertInstanceOf('React\Filesystem\ObjectStream', $stream);

        $calledOnData = false;
        $stream->on('data', function (NodeInterface $file) use (&$calledOnData) {
            $this->assertInstanceOf('React\Filesystem\Node\File', $file);
            $this->assertSame('foo.bar/bar.foo', $file->getPath());
            $calledOnData = true;
        });

        $deferred->resolve(new Payload([
            [
                'type' => 'file',
                'name' => 'bar.foo',
            ],
        ]));

        $calls = SingletonPoolStub::getCalls();
        self::assertCount(1, $calls);
        /** @var array $call */
        $call = $calls[0][1][0]->jsonSerialize();
        self::assertSame('readdir', $call['target']);
        self::assertSame([
            'path' => 'foo.bar',
            'flags' => 2,
        ], $call['payload']->getPayload());

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

        $this->await($adapter->appendContents($tempFile, $time, FILE_APPEND), $loop);
        $this->assertSame($contents, file_get_contents($tempFile));
    }
}
