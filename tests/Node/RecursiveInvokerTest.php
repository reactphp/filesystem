<?php

namespace React\Tests\Filesystem\Node;

use React\Filesystem\Node\RecursiveInvoker;
use React\Promise\FulfilledPromise;

class RecursiveInvokerTest extends \PHPUnit_Framework_TestCase
{

    public function testExecute()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface', [], [
            'futureTick',
        ]);

        $filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $loop,
        ]);
        $filesystem
            ->expects($this->once())
            ->method('getLoop')
            ->with()
            ->will($this->returnValue($loop))
        ;

        $node = $this->getMock('React\Filesystem\Node\Directory', [
            'ls',
            'getFilesystem',
            'chmod',
        ], [
            'foo.bar',
            $filesystem,
        ]);

        $node
            ->expects($this->once())
            ->method('getFilesystem')
            ->with()
            ->will($this->returnValue($filesystem))
        ;


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
            $filesystem,
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
            $filesystem,
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
        $loop
            ->expects($this->once())
            ->method('futureTick')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) use ($dents) {
                return new FulfilledPromise($resolveCb($dents));
            }))
        ;

        $promise
            ->expects($this->once())
            ->method('then')
            ->with($this->isType('callable'))
            ->will($this->returnCallback(function ($resolveCb) use ($dents) {
                return new FulfilledPromise($resolveCb($dents));
            }))
        ;

        $this->assertInstanceOf('React\Promise\PromiseInterface', (new RecursiveInvoker($node))->execute('chmod', [
            123,
        ]));
    }
}
