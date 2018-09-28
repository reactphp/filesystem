<?php

namespace React\Tests\Filesystem\Node;

use React\Filesystem\Filesystem;
use React\Filesystem\Node\File;
use React\Filesystem\Node\Directory;
use React\Filesystem\Node\RecursiveInvoker;
use React\Promise\PromiseInterface;
use React\Tests\Filesystem\TestCase;

class RecursiveInvokerTest extends TestCase
{
    public function testExecute()
    {
        $adapter = $this->mockAdapter();

        $directory = $this->getMockBuilder(Directory::class)
            ->setMethods([
                'ls',
                'chmod',
            ])
            ->setConstructorArgs([
                'foo.bar',
                Filesystem::createFromAdapter($adapter),
            ])
            ->getMock();

        $promise = $this->mockPromise();

        $fileDent = $this->getMockBuilder(File::class)
            ->setMethods([
                'chmod',
            ])
            ->setConstructorArgs([
                'foo',
                Filesystem::createFromAdapter($adapter),
            ])
            ->getMock();

        $directoryDent = $this->getMockBuilder(Directory::class)
            ->setMethods([
                'chmodRecursive',
            ])
            ->setConstructorArgs([
                'foo',
                Filesystem::createFromAdapter($adapter),
            ])
            ->getMock();

        $directoryDent
            ->expects($this->once())
            ->method('chmodRecursive')
            ->with(123)
            ->will($this->returnValue(\React\Promise\resolve()));

        $dents = [
            $fileDent,
            $directoryDent,
        ];

        $directory
            ->expects($this->once())
            ->method('ls')
            ->with()
            ->will($this->returnValue(\React\Promise\resolve($dents)));

        $directory
            ->expects($this->once())
            ->method('chmod')
            ->with(123)
            ->will($this->returnValue(\React\Promise\resolve()));

        $finalPromise = $this->mockPromise();

        $directory
            ->expects($this->once())
            ->method('chmod')
            ->with(123)
            ->will($this->returnValue($finalPromise));

        $this->assertInstanceOf(PromiseInterface::class, (new RecursiveInvoker($directory))->execute('chmod', [
            123,
        ]));
    }
}
