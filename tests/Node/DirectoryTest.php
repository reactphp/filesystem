<?php

namespace React\Tests\Filesystem\Node;

use RecursiveIterator;

use React\Filesystem\Filesystem;
use React\Filesystem\Node\File;
use React\Filesystem\Node\Directory;
use React\Filesystem\Node\NodeInterface;
use React\Filesystem\Node\RecursiveInvoker;
use React\Filesystem\Node\DirectoryInterface;
use React\Filesystem\ObjectStream;
use React\Promise\PromiseInterface;

use React\Tests\Filesystem\TestCase;

class DirectoryTest extends TestCase
{
    use NodeTestTrait;

    public function providerToString()
    {
        $ds = DIRECTORY_SEPARATOR;
        return [
            [
                'foo.bar',
                'foo.bar'.$ds,
            ],
        ];
    }

    protected function getNodeClass()
    {
        return Directory::class;
    }

    public function testGetPath()
    {
        $path = 'foo.bar';
        $this->assertSame($path . NodeInterface::DS, (new Directory($path, Filesystem::createFromAdapter($this->mockAdapter())))->getPath());
    }

    public function testLs()
    {
        $path = '/home/foo/bar';
        $filesystem = $this->mockAdapter();

        $filesystem
            ->expects($this->once())
            ->method('ls')
            ->with()
            ->will($this->returnValue(\React\Promise\resolve()));

        $directory = new Directory($path, Filesystem::createFromAdapter($filesystem));
        $this->assertInstanceOf(PromiseInterface::class, $directory->ls());
    }

    public function testCreate()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();
        $promise = \React\Promise\resolve();

        $filesystem
            ->expects($this->once())
            ->method('mkdir')
            ->with($path)
            ->will($this->returnValue($promise));

        $this->assertInstanceOf(PromiseInterface::class, (new Directory($path, Filesystem::createFromAdapter($filesystem)))->create());
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

        $newDirectory = $this->await((new Directory($pathFrom, Filesystem::createFromAdapter($filesystem)))->rename($pathTo), $this->loop);

        $this->assertInstanceOf(DirectoryInterface::class, $newDirectory);
        $this->assertSame($pathTo . NodeInterface::DS, $newDirectory->getPath());
    }

    public function testRemove()
    {
        $path = 'foo.bar';
        $filesystem = $this->mockAdapter();
        $promise = \React\Promise\resolve();

        $filesystem
            ->expects($this->once())
            ->method('rmdir')
            ->with($path)
            ->will($this->returnValue($promise));

        $this->assertSame($promise, (new Directory($path, Filesystem::createFromAdapter($filesystem)))->remove());
    }
    public function testSize()
    {
        $path = '/home/foo/bar';

        $filesystem = $this->mockAdapter();

        $lsPromise = \React\Promise\resolve('foo.bar');

        $directory = $this->getMockBuilder(Directory::class)
            ->setMethods([
                'ls',
                'processSizeContents',
            ])
            ->setConstructorArgs([
                $path,
                Filesystem::createFromAdapter($filesystem),
            ])
            ->getMock();

        $directory
            ->expects($this->once())
            ->method('ls')
            ->with()
            ->will($this->returnValue($lsPromise));

        $directory
            ->expects($this->once())
            ->method('processSizeContents')
            ->with('foo.bar', $this->isType('boolean'))
            ->will($this->returnValue(\React\Promise\resolve()));

        $this->assertInstanceOf(PromiseInterface::class, $directory->size());
    }

    public function testChmodRecursive()
    {
        $filesystem = $this->mockAdapter();
        $promise = \React\Promise\resolve();

        $recursiveInvoker = $this->getMockBuilder(RecursiveInvoker::class)
            ->setMethods([
                'execute',
            ])
            ->setConstructorArgs([
                $this->getMockBuilder(DirectoryInterface::class)
                    ->setConstructorArgs([
                        'foo/bar/',
                        Filesystem::createFromAdapter($filesystem),
                    ])
                    ->getMock(),
            ])
            ->getMock();

        $recursiveInvoker
            ->expects($this->once())
            ->method('execute')
            ->with('chmod', [123])
            ->will($this->returnValue($promise));

        $this->assertSame($promise, (new Directory('foo/bar/', Filesystem::createFromAdapter($filesystem), $recursiveInvoker))->chmodRecursive(123));
    }

    public function testChownRecursive()
    {
        $filesystem = $this->mockAdapter();
        $promise = \React\Promise\resolve();

        $recursiveInvoker = $this->getMockBuilder(RecursiveInvoker::class)
            ->setMethods([
                'execute',
            ])
            ->setConstructorArgs([
                $this->getMockBuilder(DirectoryInterface::class)
                    ->setConstructorArgs([
                        'foo/bar/',
                        Filesystem::createFromAdapter($filesystem),
                    ])
                    ->getMock(),
            ])
            ->getMock();

        $recursiveInvoker
            ->expects($this->once())
            ->method('execute')
            ->with('chown', [1, 2])
            ->will($this->returnValue($promise));

        $this->assertSame($promise, (new Directory('foo/bar/', Filesystem::createFromAdapter($filesystem), $recursiveInvoker))->chownRecursive(1, 2));
    }

    public function testRemoveRecursive()
    {
        $filesystem = $this->mockAdapter();
        $promise = \React\Promise\resolve();

        $recursiveInvoker = $this->getMockBuilder(RecursiveInvoker::class)
            ->setMethods([
                'execute',
            ])
            ->setConstructorArgs([
                $this->getMockBuilder(DirectoryInterface::class)
                    ->setConstructorArgs([
                        'foo/bar/',
                        Filesystem::createFromAdapter($filesystem),
                    ])
                    ->getMock(),
            ])
            ->getMock();

        $recursiveInvoker
            ->expects($this->once())
            ->method('execute')
            ->with('remove', [])
            ->will($this->returnValue($promise));

        $this->assertSame($promise, (new Directory('foo/bar/', Filesystem::createFromAdapter($filesystem), $recursiveInvoker))->removeRecursive());
    }

    public function testCopy()
    {
        $filesystem = $this->mockAdapter();

        $directoryFrom = $this->getMockBuilder(Directory::class)
            ->setMethods([
                'copyStreaming',
            ])
            ->setConstructorArgs([
                'foo.bar',
                Filesystem::createFromAdapter($filesystem),
            ])
            ->getMock();

        $fileTo = new Directory('bar.foo', Filesystem::createFromAdapter($filesystem));

        $directoryFrom
            ->expects($this->once())
            ->method('copyStreaming')
            ->with($fileTo)
            ->will($this->returnValue(new ObjectStream()));

        $promise = $directoryFrom->copy($fileTo);
        $this->assertInstanceOf(PromiseInterface::class, $promise);
    }

    public function testCopyStreamingABC()
    {
        $ds = DIRECTORY_SEPARATOR;
        $adapter = $this->mockAdapter();

        $filesystem = Filesystem::createFromAdapter($adapter);

        $adapter
            ->expects($this->at(0))
            ->method('stat')
            ->with('bar.foo'.$ds.'foo.bar'.$ds)
            ->will($this->returnValue(\React\Promise\reject()));

        $adapter
            ->expects($this->at(1))
            ->method('stat')
            ->with('bar.foo'.$ds)
            ->will($this->returnValue(\React\Promise\resolve()));

        /*$adapter
            ->expects($this->at(3))
            ->method('stat')
            ->with('bar.foo'.$ds.'foo.bar'.$ds)
            ->will($this->returnValue(\React\Promise\resolve()));*/

        $adapter
            ->expects($this->any())
            ->method('mkdir')
            ->with($this->isType('string'))
            ->will($this->returnValue(\React\Promise\resolve()));

        $directoryFrom = $this->getMockBuilder(Directory::class)
            ->setMethods([
            'ls',
        ])
        ->setConstructorArgs([
            'foo.bar',
            $filesystem,
        ])
        ->getMock();

        $fileStream = new ObjectStream();

        $file = $this->getMockBuilder(File::class, [
            'copyStreaming',
        ])
        ->setConstructorArgs([
            'foo.bar',
            $filesystem,
        ])
        ->getMock();

        $directoryTo = new Directory('bar.foo', $filesystem);

        $file
            ->expects($this->once())
            ->method('copyStreaming')
            ->with($directoryTo)
            ->will($this->returnValue($fileStream));

            $directory = $this->getMockBuilder(Directory::class)
                ->setMethods([
                'copyStreaming',
            ])
            ->setConstructorArgs([
                'foo.bar',
                $filesystem,
            ])
            ->getMock();

            $directoryStream = new ObjectStream();

            $directory
                ->expects($this->once())
                ->method('copyStreaming')
                ->with($this->isInstanceOf(Directory::class))
                ->will($this->returnValue($directoryStream));

        $streamPromise = \React\Promise\resolve([
            $file,
            $directory,
        ]);

        $directoryFrom
            ->expects($this->once())
            ->method('ls')
            ->with()
            ->will($this->returnValue($streamPromise));

        $returnedStream = $directoryFrom->copyStreaming($directoryTo);
        $this->assertInstanceOf(ObjectStream::class, $returnedStream);

        $directoryStream->end($directory);
    }
}
