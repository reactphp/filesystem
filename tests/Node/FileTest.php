<?php

namespace React\Tests\Filesystem\Node;

use React\Filesystem\Node\Directory;
use React\Filesystem\Node\File;
use React\Filesystem\ObjectStream;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;
use React\Tests\Filesystem\UnknownNodeType;

class FileTest extends \PHPUnit_Framework_TestCase
{
    use NodeTestTrait;

    public function providerToString()
    {
        return [
            [
                'foo.bar',
                'foo.bar',
            ],
        ];
    }

    protected function getNodeClass()
    {
        return 'React\Filesystem\Node\File';
    }

    public function testGetPath()
    {
        $path = 'foo.bar';
        $this->assertSame($path, (new File($path, $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ])))->getPath());
    }

    public function testRemove()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
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
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
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

    public function testExists()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $file = $this->getMock('React\Filesystem\Node\File', [
            'stat',
        ], [
            $path,
            $filesystem,
        ]);

        $promise = $this->getMock('React\Promise\PromiseInterface');

        $promise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) {
                return $resolveCb();
            }))
        ;

        $file
            ->expects($this->once())
            ->method('stat')
            ->with()
            ->will($this->returnValue($promise))
        ;

        $this->assertInstanceOf('React\Promise\PromiseInterface', $file->exists());
    }

    public function testDoesntExists()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $file = $this->getMock('React\Filesystem\Node\File', [
            'stat',
        ], [
            $path,
            $filesystem,
        ]);

        $promise = $this->getMock('React\Promise\PromiseInterface');

        $promise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($null, $resolveCb) {
                return $resolveCb();
            }))
        ;

        $file
            ->expects($this->once())
            ->method('stat')
            ->with()
            ->will($this->returnValue($promise))
        ;

        $this->assertInstanceOf('React\Promise\PromiseInterface', $file->exists());
    }

    public function testSize()
    {
        $size = 1337;
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'stat',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $deferred = new Deferred();
        $promise = $deferred->promise();

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $sizePromise = (new File($path, $filesystem))->size();
        $this->assertInstanceOf('React\Promise\PromiseInterface', $sizePromise);

        $callbackFired = false;
        $sizePromise->then(function ($resultSize) use ($size, &$callbackFired) {
            $this->assertSame($size, $resultSize);
            $callbackFired = true;
        });
        $deferred->resolve([
            'size' => $size,
        ]);
        $this->assertTrue($callbackFired);
    }

    public function testTime()
    {
        $times = [
            'atime' => 1,
            'ctime' => 2,
            'mtime' => 3,
        ];
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'stat',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $deferred = new Deferred();
        $promise = $deferred->promise();

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $timePromise = (new File($path, $filesystem))->time();
        $this->assertInstanceOf('React\Promise\PromiseInterface', $timePromise);

        $callbackFired = false;
        $timePromise->then(function ($time) use ($times, &$callbackFired) {
            $this->assertSame($times, $time);
            $callbackFired = true;
        });
        $deferred->resolve($times);
        $this->assertTrue($callbackFired);
    }

    public function testCreate()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'stat',
            'touch',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue(new RejectedPromise()))
        ;

        $filesystem
            ->expects($this->once())
            ->method('touch')
            ->with($path)
            ->will($this->returnValue(new FulfilledPromise()))
        ;

        $callbackFired = false;
        (new File($path, $filesystem))->create()->then(function () use (&$callbackFired) {
            $callbackFired = true;
        });

        $this->assertTrue($callbackFired);
    }

    public function testCreateFail()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'stat',
            'touch',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue(new FulfilledPromise()))
        ;

        $callbackFired = false;
        (new File($path, $filesystem))->create()->then(null, function ($e) use (&$callbackFired) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertSame('File exists', $e->getMessage());
            $callbackFired = true;
        });

        $this->assertTrue($callbackFired);
    }

    public function testOpen()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'open',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $stream = $this->getMock('React\Filesystem\Stream\GenericStreamInterface', [], ['foo:bar']);
        $flags = 'abc';

        $filesystem
            ->expects($this->once())
            ->method('open')
            ->with($path, $flags)
            ->will($this->returnValue(new FulfilledPromise($stream)))
        ;

        $callbackFired = false;
        (new File($path, $filesystem))->open($flags)->then(function ($passStream) use (&$callbackFired, $stream) {
            $this->assertSame($stream, $passStream);
            $callbackFired = true;
        });

        $this->assertTrue($callbackFired);
    }


    public function testOpenTwice()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'open',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $stream = $this->getMock('React\Filesystem\Stream\GenericStreamInterface', [], ['foo:bar']);
        $flags = 'abc';

        $filesystem
            ->expects($this->once())
            ->method('open')
            ->with($path, $flags)
            ->will($this->returnValue(new FulfilledPromise($stream)))
        ;

        $file = new File($path, $filesystem);
        $file->open($flags);
        $this->assertInstanceOf('React\Promise\RejectedPromise', $file->open($flags));
    }

    public function testGetContents()
    {
        $path = 'foo.bar';
        $fd = '0123456789abcdef';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'open',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $stream = $this->getMock('React\Filesystem\Stream\GenericStreamInterface', [
            'getFiledescriptor',
        ], [
            'foo:bar',
        ]);

        $stream
            ->expects($this->once())
            ->method('getFiledescriptor')
            ->with()
            ->will($this->returnValue($fd))
        ;

        $openPromise = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $openPromise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) use ($stream) {
                return new FulfilledPromise($resolveCb($stream));
            }))
        ;

        $filesystem
            ->expects($this->once())
            ->method('open')
            ->with($path, 'r')
            ->will($this->returnValue($openPromise))
        ;

        $getContentsPromise = (new File($path, $filesystem))->getContents();
        $this->assertInstanceOf('React\Promise\PromiseInterface', $getContentsPromise);
    }

    public function testClose()
    {
        $path = 'foo.bar';
        $fd = '0123456789abcdef';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'close',
            'open',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $stream = $this->getMock('React\Filesystem\Stream\GenericStreamInterface', [
            'getFiledescriptor',
        ], [
            'foo:bar',
        ]);

        $stream
            ->expects($this->once())
            ->method('getFiledescriptor')
            ->with()
            ->will($this->returnValue($fd))
        ;

        $openPromise = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $openPromise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) use ($stream) {
                return new FulfilledPromise($resolveCb($stream));
            }))
        ;

        $filesystem
            ->expects($this->once())
            ->method('open')
            ->with($path, 'r')
            ->will($this->returnValue($openPromise))
        ;

        $closePromise = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $closePromise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) use ($stream) {
                return \React\Promise\resolve($resolveCb($stream));
            }))
        ;

        $filesystem
            ->expects($this->once())
            ->method('close')
            ->with($fd)
            ->will($this->returnValue($closePromise))
        ;

        $file = new File($path, $filesystem);
        $file->open('r');
        $file->close();
    }

    public function testCloseNeverOpened()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $this->assertInstanceOf('React\Promise\RejectedPromise', (new File($path, $filesystem))->close());
    }

    public function testTouch()
    {
        $path = 'foo.bar';
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'touch',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $filesystem
            ->expects($this->once())
            ->method('touch')
            ->with($path)
            ->will($this->returnValue($this->getMock('React\Promise\PromiseInterface')))
        ;

        $this->assertInstanceOf('React\Promise\PromiseInterface', (new File($path, $filesystem))->touch());
    }

    public function testCopy()
    {
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $fileFrom = $this->getMock('React\Filesystem\Node\File', [
            'copyStreaming',
        ], [
            'foo.bar',
            $filesystem,
        ]);

        $fileTo = new File('bar.foo', $filesystem);

        $fileFrom
            ->expects($this->once())
            ->method('copyStreaming')
            ->with($fileTo)
            ->will($this->returnValue(new ObjectStream()))
        ;

        $stream = $fileFrom->copy($fileTo);
        $this->assertInstanceOf('React\Promise\PromiseInterface', $stream);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testCopyUnknownNode()
    {
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        (new File('foo.bar', $filesystem))->copy(new UnknownNodeType());
    }

    public function testCopyFile()
    {
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $fileFrom = $this->getMock('React\Filesystem\Node\File', [
            'open',
        ], [
            'foo.bar',
            $filesystem,
        ]);

        $streamFrom = $this->getMock('React\Filesystem\Stream\ReadableStreamInterface', [
            'pipe',

            // Must define these...
            'isReadable',
            'pause',
            'resume',
            'close',
            'on',
            'once',
            'removeListener',
            'removeAllListeners',
            'listeners',
            'emit',
        ]);

        $fileTo = $this->getMock('React\Filesystem\Node\File', [
            'open',
        ], [
            'foo.bar',
            $filesystem,
        ]);

        $streamTo = $this->getMock('React\Filesystem\Stream\WritableStreamInterface', [
            'on',

            // Must define these...
            'isWritable',
            'write',
            'end',
            'close',
            'once',
            'removeListener',
            'removeAllListeners',
            'listeners',
            'emit',
        ]);

        $promiseFrom = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $promiseTo = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $streamFrom
            ->expects($this->once())
            ->method('pipe')
            ->with($streamTo)
            ->will($this->returnValue($streamTo))
        ;

        $fileFrom
            ->expects($this->once())
            ->method('open')
            ->with('r', 'rwxrwx---')
            ->will($this->returnValue($promiseFrom))
        ;

        $fileTo
            ->expects($this->once())
            ->method('open')
            ->with('ctw', 'rwxrwx---')
            ->will($this->returnValue($promiseTo))
        ;

        $streamTo
            ->expects($this->once())
            ->method('on')
            ->with('end', $this->isType('callable'))
            ->will($this->returnCallback(function ($end, $callable) {
                $callable();
            }))
        ;

        $promiseFrom
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) use ($streamFrom) {
                return $resolveCb($streamFrom);
            }))
        ;

        $promiseTo
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) use ($streamTo) {
                return $resolveCb($streamTo);
            }))
        ;

        $stream = $fileFrom->copyStreaming($fileTo);
        $this->assertInstanceOf('React\Filesystem\ObjectStream', $stream);
    }

    public function testCopyDirectory()
    {
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $file = $this->getMock('React\Filesystem\Node\File', [
            'copyToFile',
        ], [
            'foo.bar',
            $filesystem,
        ]);

        $directoryTo = new Directory('bar.foo', $filesystem);

        $file
            ->expects($this->once())
            ->method('copyToFile')
            ->with($this->isInstanceOf('React\Filesystem\Node\File'))
            ->will($this->returnValue(new ObjectStream()))
        ;

        $stream = $file->copyStreaming($directoryTo);
        $this->assertInstanceOf('React\Filesystem\ObjectStream', $stream);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testCopyStreamingUnknownNode()
    {
        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        (new File('foo.bar', $filesystem))->copyStreaming(new UnknownNodeType());
    }
}
