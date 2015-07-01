<?php

namespace React\Tests\Filesystem\Node;

use React\Filesystem\Node\File;
use React\Promise\FulfilledPromise;

class GenericOperationTraitTest extends \PHPUnit_Framework_TestCase
{

    public function testGetFilesystem()
    {
        $got = $this->getMockForTrait('React\Filesystem\Node\GenericOperationTrait');

        $got->filesystem = $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);

        $this->assertSame($got->filesystem, $got->getFilesystem());
    }

    public function testStat()
    {
        $got = $this->getMockForTrait('React\Filesystem\Node\GenericOperationTrait', [], '', true, true, true, [
            'getPath',
        ]);
        $got->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('foo.bar'));

        $promise = new FulfilledPromise();

        $got->filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'stat',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $got->filesystem->expects($this->once())
            ->method('stat')
            ->with('foo.bar')
            ->will($this->returnValue($promise));

        $this->assertSame($promise, $got->stat());
    }

    public function testChmod()
    {
        $got = $this->getMockForTrait('React\Filesystem\Node\GenericOperationTrait', [], '', true, true, true, [
            'getPath',
        ]);
        $got->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('foo.bar'));

        $promise = new FulfilledPromise();

        $got->filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'chmod',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $got->filesystem->expects($this->once())
            ->method('chmod')
            ->with('foo.bar', 'abc')
            ->will($this->returnValue($promise));

        $this->assertSame($promise, $got->chmod('abc'));
    }

    public function testChown()
    {
        $got = $this->getMockForTrait('React\Filesystem\Node\GenericOperationTrait', [], '', true, true, true, [
            'getPath',
        ]);
        $got->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('foo.bar'));

        $promise = new FulfilledPromise();

        $got->filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'chown',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $got->filesystem->expects($this->once())
            ->method('chown')
            ->with('foo.bar', 1, 2)
            ->will($this->returnValue($promise));

        $this->assertSame($promise, $got->chown(1, 2));
    }

    public function testChownDefaults()
    {
        $got = $this->getMockForTrait('React\Filesystem\Node\GenericOperationTrait', [], '', true, true, true, [
            'getPath',
        ]);
        $got->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('foo.bar'));

        $promise = new FulfilledPromise();

        $got->filesystem = $this->getMock('React\Filesystem\EioAdapter', [
            'chown',
        ], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]);
        $got->filesystem->expects($this->once())
            ->method('chown')
            ->with('foo.bar', -1, -1)
            ->will($this->returnValue($promise));

        $this->assertSame($promise, $got->chown());
    }

    public function testCreateNameNParentFromFilename()
    {
        $node = new File('/foo/bar/baz/rabbit/kitten/index.php', $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]));

        foreach ([
            [
                'index.php',
                '/foo/bar/baz/rabbit/kitten/index.php',
            ],
            [
                'kitten',
                '/foo/bar/baz/rabbit/kitten',
            ],
            [
                'rabbit',
                '/foo/bar/baz/rabbit',
            ],
            [
                'baz',
                '/foo/bar/baz',
            ],
            [
                'bar',
                '/foo/bar',
            ],
            [
                'foo',
                '/foo',
            ],
            [
                '',
                '',
            ],
        ] as $names) {
            $this->assertSame($names[0], $node->getName());
            $this->assertSame($names[1], $node->getPath());
            $node = $node->getParent();
        }
    }
}
