<?php

namespace React\Tests\Filesystem\Node;

use Exception;
use React\Filesystem\Filesystem;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Node\Directory;
use React\Filesystem\Node\File;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\ObjectStream;
use React\Filesystem\Stream\ReadableStream;
use React\Filesystem\Stream\WritableStream;
use React\Filesystem\Stream\GenericStreamInterface;
use React\Promise\Deferred;
use React\Promise\RejectedPromise;
use React\Promise\PromiseInterface;
use React\Tests\Filesystem\TestCase;

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
        return File::class;
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

        $promise = \React\Promise\resolve();

        $filesystem
            ->expects($this->once())
            ->method('unlink')
            ->with($path)
            ->will($this->returnValue($promise));

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
            ->will($this->returnValue(\React\Promise\resolve()));

        $newFile = $this->await((new File($pathFrom, Filesystem::createFromAdapter($filesystem)))->rename($pathTo), $this->loop);

        $this->assertInstanceOf(FileInterface::class, $newFile);
        $this->assertSame($pathTo, $newFile->getPath());
    }

    public function testExists()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();

        $file = $this->getMockBuilder(File::class)
            ->setMethods([
                'stat',
            ])
            ->setConstructorArgs([
                $path,
                Filesystem::createFromAdapter($filesystem),
            ])
            ->getMock();

        $promise = \React\Promise\resolve();

        $file
            ->expects($this->once())
            ->method('stat')
            ->with()
            ->will($this->returnValue($promise));

        $this->assertInstanceOf(PromiseInterface::class, $file->exists());
    }

    public function testDoesntExists()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();

        $file = $this->getMockBuilder(File::class)
            ->setMethods([
                'stat',
            ])
            ->setConstructorArgs([
                $path,
                Filesystem::createFromAdapter($filesystem),
            ])
            ->getMock();

        $promise = \React\Promise\resolve();

        $file
            ->expects($this->once())
            ->method('stat')
            ->with()
            ->will($this->returnValue($promise));

        $this->assertInstanceOf(PromiseInterface::class, $file->exists());
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
            ->will($this->returnValue($promise));

        $sizePromise = (new File($path, Filesystem::createFromAdapter($filesystem)))->size();
        $this->assertInstanceOf(PromiseInterface::class, $sizePromise);

        $deferred->resolve([
            'size' => $size,
        ]);

        $resultSize = $this->await($sizePromise, $this->loop);
        $this->assertSame($size, $resultSize);
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
            ->will($this->returnValue($promise));

        $timePromise = (new File($path, Filesystem::createFromAdapter($filesystem)))->time();
        $this->assertInstanceOf(PromiseInterface::class, $timePromise);

        $deferred->resolve($times);
        $time = $this->await($timePromise, $this->loop);

        $this->assertSame($times, $time);
    }

    public function testCreate()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue(\React\Promise\reject()));

        $filesystem
            ->expects($this->once())
            ->method('touch')
            ->with($path)
            ->will($this->returnValue(\React\Promise\resolve()));

        $this->assertNull($this->await((new File($path, Filesystem::createFromAdapter($filesystem)))->create(), $this->loop));
    }

    public function testCreateFail()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->once())
            ->method('stat')
            ->with($path)
            ->will($this->returnValue(\React\Promise\resolve()));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File exists');

        $this->await((new File($path, Filesystem::createFromAdapter($filesystem)))->create(), $this->loop);
    }

    public function testOpen()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();

        $flags = 'abc';

        $filesystem
            ->expects($this->once())
            ->method('open')
            ->with($path, $flags, AdapterInterface::CREATION_MODE)
            ->will($this->returnValue(\React\Promise\resolve('foo:bar')));

        $passStream = $this->await((new File($path, Filesystem::createFromAdapter($filesystem)))->open($flags), $this->loop);
        $this->assertInstanceOf(GenericStreamInterface::class, $passStream);
    }


    public function testOpenTwice()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();

        $stream = $this->getMockBuilder(GenericStreamInterface::class)
            ->setConstructorArgs(['foo:bar'])
            ->getMock();

        $flags = 'abc';

        $filesystem
            ->expects($this->once())
            ->method('open')
            ->with($path, $flags)
            ->will($this->returnValue(\React\Promise\resolve($stream)));

        $file = new File($path, Filesystem::createFromAdapter($filesystem));
        $file->open($flags);

        $this->assertInstanceOf(RejectedPromise::class, $file->open($flags));
    }

    public function testGetContents()
    {
        $path = 'foo.bar';
        $fd = '0123456789abcdef';

        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->exactly(2))
            ->method('stat')
            ->with($path)
            ->will($this->returnValue(\React\Promise\resolve([
                'size' => 1,
            ])));

        $filesystem
            ->expects($this->once())
            ->method('open')
            ->with($path, 'r')
            ->will($this->returnValue(\React\Promise\resolve($fd)));

        $filesystem
            ->expects($this->exactly(2))
            ->method('read')
            ->with($fd, 1, 0)
            ->will($this->returnValue(\React\Promise\resolve('a')));

        $filesystem
            ->expects($this->exactly(2))
            ->method('close')
            ->with($fd)
            ->will($this->returnValue(\React\Promise\resolve()));

        $stream = new ReadableStream(
            $path,
            $fd,
            $filesystem
        );

        $getContentsPromise = (new File($path, Filesystem::createFromAdapter($filesystem)))->getContents();
        $this->assertInstanceOf(PromiseInterface::class, $getContentsPromise);
    }

    public function testPutContents()
    {
        $path = 'foo.bar';
        $fd = '0123456789abcdef';
        $content = 'gfrdsag34t5';

        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->once())
            ->method('open')
            ->with($path, 'cw')
            ->will($this->returnValue(\React\Promise\resolve($fd)));

        $filesystem
            ->expects($this->once())
            ->method('write')
            ->with($fd, $content, 11, 0)
            ->will($this->returnValue(\React\Promise\resolve()));

        $filesystem
            ->expects($this->once())
            ->method('close')
            ->with($fd)
            ->will($this->returnValue(\React\Promise\resolve()));

        $stream = new WritableStream(
            $path,
            $fd,
            $filesystem
        );

        $putContentsPromise = (new File($path, Filesystem::createFromAdapter($filesystem)))->putContents($content);
        $this->assertInstanceOf(PromiseInterface::class, $putContentsPromise);
    }

    public function testClose()
    {
        $path = 'foo.bar';
        $fd = '0123456789abcdef';
        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->once())
            ->method('open')
            ->with($path, 'r')
            ->will($this->returnValue(\React\Promise\resolve($fd)));

        $filesystem
            ->expects($this->once())
            ->method('close')
            ->with($fd)
            ->will($this->returnValue(\React\Promise\resolve()));

        $file = new File($path, Filesystem::createFromAdapter($filesystem));

        $file->open('r');
        $file->close();
    }

    public function testCloseNeverOpened()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();
        $this->assertInstanceOf(RejectedPromise::class, (new File($path, Filesystem::createFromAdapter($filesystem)))->close());
    }

    public function testTouch()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->once())
            ->method('touch')
            ->with($path)
            ->will($this->returnValue(\React\Promise\resolve()));

        $this->assertInstanceOf(PromiseInterface::class, (new File($path, Filesystem::createFromAdapter($filesystem)))->touch());
    }

    public function testCopy()
    {
        $filesystem = $this->mockAdapter();

        $fileFrom = $this->getMockBuilder(File::class)
            ->setMethods([
                'copyStreaming',
            ])
            ->setConstructorArgs([
                'foo.bar',
                Filesystem::createFromAdapter($filesystem),
            ])
            ->getMock();

        $fileTo = new File('bar.foo', Filesystem::createFromAdapter($filesystem));

        $fileFrom
            ->expects($this->once())
            ->method('copyStreaming')
            ->with($fileTo)
            ->will($this->returnValue(new ObjectStream()));

        $stream = $fileFrom->copy($fileTo);
        $this->assertInstanceOf(PromiseInterface::class, $stream);
    }

    public function testCopyStreaming()
    {
        $filesystem = $this->mockAdapter();

        $fileFrom = $this->getMockBuilder(File::class)
            ->setMethods([
                'copyStreaming',
            ])
            ->setConstructorArgs([
                'foo.bar',
                Filesystem::createFromAdapter($filesystem),
            ])
            ->getMock();

        $fileTo = new File('bar.foo', Filesystem::createFromAdapter($filesystem));

        $fileFrom
            ->expects($this->once())
            ->method('copyStreaming')
            ->with($fileTo)
            ->will($this->returnValue(new ObjectStream()));

        $stream = $fileFrom->copyStreaming($fileTo);
        $this->assertInstanceOf(ObjectStream::class, $stream);
    }

    public function testCopyDirectory()
    {
        $filesystem = $this->mockAdapter();

        $file = $this->getMockBuilder(File::class)
            ->setMethods([
                'copyToFile',
            ])
            ->setConstructorArgs([
                'foo.bar',
                Filesystem::createFromAdapter($filesystem),
            ])
            ->getMock();

        $directoryTo = new Directory('bar.foo', Filesystem::createFromAdapter($filesystem));

        $file
            ->expects($this->once())
            ->method('copyToFile')
            ->with($this->isInstanceOf(File::class))
            ->will($this->returnValue(new ObjectStream()));

        $stream = $file->copyStreaming($directoryTo);
        $this->assertInstanceOf(ObjectStream::class, $stream);
    }
}
