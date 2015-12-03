<?php

namespace React\Tests\Filesystem\Node;

use React\Filesystem\Filesystem;
use React\Filesystem\Node\RecursiveInvoker;
use React\Promise\FulfilledPromise;
use React\Tests\Filesystem\TestCase;

class RecursiveInvokerTest extends TestCase
{

    public function testExecute()
    {
        $filesystem = $this->mockAdapter();

        $node = $this->getMock('React\Filesystem\Node\Directory', [
            'ls',
            'chmod',
        ], [
            'foo.bar',
            Filesystem::createFromAdapter($filesystem),
        ]);

        $promise = $this->getMock('React\Promise\PromiseInterface');

        $node
            ->expects($this->once())
            ->method('ls')
            ->with()
            ->will($this->returnValue($promise))
        ;

        $fileDent = $this->getMock('React\Filesystem\Node\File', [
            'chmod',
        ], [
            'foo',
            Filesystem::createFromAdapter($filesystem),
        ]);

        $node
            ->expects($this->once())
            ->method('chmod')
            ->with(123)
            ->will($this->returnValue(new FulfilledPromise()))
        ;

        $directoryDent = $this->getMock('React\Filesystem\Node\Directory', [
            'chmodRecursive',
        ], [
            'foo',
            Filesystem::createFromAdapter($filesystem),
        ]);

        $directoryDent
            ->expects($this->once())
            ->method('chmodRecursive')
            ->with(123)
            ->will($this->returnValue(new FulfilledPromise()))
        ;

        $finalPromise = $this->getMock('React\Promise\PromiseInterface');

        $node
            ->expects($this->once())
            ->method('chmod')
            ->with(123)
            ->will($this->returnValue($finalPromise))
        ;

        $dents = [
            $fileDent,
            $directoryDent,
        ];

        $promise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) use ($dents) {
                return $resolveCb($dents);
            }))
        ;

        $this->assertInstanceOf('React\Promise\PromiseInterface', (new RecursiveInvoker($node))->execute('chmod', [
            123,
        ]));
    }
}
