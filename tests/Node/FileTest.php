<?php

namespace React\Tests\Filesystem\Node;

use React\EventLoop\Factory;
use React\Filesystem\Filesystem;
use React\Filesystem\Node\Directory;
use React\Filesystem\Node\File;
use React\Filesystem\ObjectStream;
use React\Filesystem\Stream\ReadableStream;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;
use React\Tests\Filesystem\TestCase;
use React\Tests\Filesystem\UnknownNodeType;

class FileTest extends TestCase
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
        $this->assertSame($path, (new File($path, Filesystem::createFromAdapter($this->mockAdapter())))->getPath());
    }

    public function testRemove()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();
        $promise = $this->getMock('React\Promise\PromiseInterface');

        $filesystem
            ->expects($this->once())
            ->method('unlink')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $this->assertSame($promise, (new File($path, Filesystem::createFromAdapter($filesystem)))->remove());
    }

    public function testRename()
    {
        $pathFrom = 'foo.bar';
        $pathTo = 'bar.foo';
        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->once())
            ->method('rename')
            ->with($pathFrom, $pathTo)
            ->will($this->returnValue(new FulfilledPromise()))
        ;

        $newFile = \Clue\React\Block\await((new File($pathFrom, Filesystem::createFromAdapter($filesystem)))->rename($pathTo), Factory::create());
        $this->assertInstanceOf('React\Filesystem\Node\FileInterface', $newFile);
        $this->assertSame($pathTo, $newFile->getPath());
    }

    public function testExists()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();

        $file = $this->getMock('React\Filesystem\Node\File', [
            'stat',
        ], [
            $path,
            Filesystem::createFromAdapter($filesystem),
        ]);

        $promise = \React\Promise\resolve();

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
        $filesystem = $this->mockAdapter();

        $file = $this->getMock('React\Filesystem\Node\File', [
            'stat',
        ], [
            $path,
            Filesystem::createFromAdapter($filesystem),
        ]);

        $promise = \React\Promise\resolve();

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
        $filesystem = $this->mockAdapter();
        $deferred = new Deferred();
        $promise = $deferred->promise();

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $sizePromise = (new File($path, Filesystem::createFromAdapter($filesystem)))->size();
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
        $filesystem = $this->mockAdapter();
        $deferred = new Deferred();
        $promise = $deferred->promise();

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue($promise))
        ;

        $timePromise = (new File($path, Filesystem::createFromAdapter($filesystem)))->time();
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
        $filesystem = $this->mockAdapter();

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
        (new File($path, Filesystem::createFromAdapter($filesystem)))->create()->then(function () use (&$callbackFired) {
            $callbackFired = true;
        });

        $this->assertTrue($callbackFired);
    }

    public function testCreateFail()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue(new FulfilledPromise()))
        ;

        $callbackFired = false;
        (new File($path, Filesystem::createFromAdapter($filesystem)))->create()->then(null, function ($e) use (&$callbackFired) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertSame('File exists already', $e->getMessage());
            $callbackFired = true;
        });

        $this->assertTrue($callbackFired);
    }

    public function testOpen()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();

        $stream = $this->getMock('React\Filesystem\Stream\GenericStreamInterface', [], ['foo:bar']);
        $flags = 'abc';

        $filesystem
            ->expects($this->once())
            ->method('open')
            ->with($path, $flags)
            ->will($this->returnValue(new FulfilledPromise($stream)))
        ;

        $callbackFired = false;
        (new File($path, Filesystem::createFromAdapter($filesystem)))->open($flags)->then(function ($passStream) use (&$callbackFired, $stream) {
            $this->assertSame($stream, $passStream);
            $callbackFired = true;
        });

        $this->assertTrue($callbackFired);
    }


    public function testOpenTwice()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();

        $stream = $this->getMock('React\Filesystem\Stream\GenericStreamInterface', [], ['foo:bar']);
        $flags = 'abc';

        $filesystem
            ->expects($this->once())
            ->method('open')
            ->with($path, $flags)
            ->will($this->returnValue(new FulfilledPromise($stream)))
        ;

        $file = new File($path, Filesystem::createFromAdapter($filesystem));
        $file->open($flags);
        $this->assertInstanceOf('React\Promise\RejectedPromise', $file->open($flags));
    }

    public function testGetContents()
    {
        $path = 'foo.bar';
        $fd = '0123456789abcdef';

        $openPromise = $this->getMock('React\Promise\PromiseInterface', [
            'then',
        ]);

        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue(new FulfilledPromise([
                'size' => 1,
            ])))
        ;

        $filesystem
            ->expects($this->once())
            ->method('open')
            ->with($path, 'r')
            ->will($this->returnValue($openPromise))
        ;

        $filesystem
            ->expects($this->once())
            ->method('read')
            ->with($fd, 1, 0)
            ->will($this->returnValue(new FulfilledPromise(str_repeat('a', 1))))
        ;

        $filesystem
            ->expects($this->once())
            ->method('close')
            ->with($fd)
            ->will($this->returnValue(new FulfilledPromise()))
        ;

        $stream = new ReadableStream(
            $path,
            $fd,
            $filesystem
        );

        $openPromise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) use ($stream) {
                return new FulfilledPromise($resolveCb($stream));
            }))
        ;

        $getContentsPromise = (new File($path, Filesystem::createFromAdapter($filesystem)))->getContents();
        $this->assertInstanceOf('React\Promise\PromiseInterface', $getContentsPromise);
    }

    public function testClose()
    {
        $path = 'foo.bar';
        $fd = '0123456789abcdef';
        $filesystem = $this->mockAdapter();

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

        $file = new File($path, Filesystem::createFromAdapter($filesystem));
        $file->open('r');
        $file->close();
    }

    public function testCloseNeverOpened()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();
        $this->assertInstanceOf('React\Promise\RejectedPromise', (new File($path, Filesystem::createFromAdapter($filesystem)))->close());
    }

    public function testTouch()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->once())
            ->method('touch')
            ->with($path)
            ->will($this->returnValue($this->getMock('React\Promise\PromiseInterface')))
        ;

        $this->assertInstanceOf('React\Promise\PromiseInterface', (new File($path, Filesystem::createFromAdapter($filesystem)))->touch());
    }

    public function testCopy()
    {
        $filesystem = $this->mockAdapter();

        $fileFrom = $this->getMock('React\Filesystem\Node\File', [
            'copyStreaming',
        ], [
            'foo.bar',
            Filesystem::createFromAdapter($filesystem),
        ]);

        $fileTo = new File('bar.foo', Filesystem::createFromAdapter($filesystem));

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
        $filesystem = $this->mockAdapter();

        (new File('foo.bar', Filesystem::createFromAdapter($filesystem)))->copy(new UnknownNodeType());
    }

    public function testCopyDirectory()
    {
        $filesystem = $this->mockAdapter();

        $file = $this->getMock('React\Filesystem\Node\File', [
            'copyToFile',
        ], [
            'foo.bar',
            Filesystem::createFromAdapter($filesystem),
        ]);

        $directoryTo = new Directory('bar.foo', Filesystem::createFromAdapter($filesystem));

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
        $filesystem = $this->mockAdapter();

        (new File('foo.bar', Filesystem::createFromAdapter($filesystem)))->copyStreaming(new UnknownNodeType());
    }
}
