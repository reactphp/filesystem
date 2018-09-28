<?php

namespace React\Tests\Filesystem\Node;

use React\Filesystem\Filesystem;
use React\Filesystem\Node\File;
use React\Filesystem\Node\GenericOperationTrait;
use React\Tests\Filesystem\TestCase;

class GenericOperationTraitTest extends TestCase
{
    public function testGetFilesystem()
    {
        $got = $this->getMockForTrait(GenericOperationTrait::class);

        $got->adapter = $this->mockAdapter();

        $got->filesystem = Filesystem::createFromAdapter($got->adapter);

        $this->assertSame($got->filesystem, $got->getFilesystem());
        $this->assertSame($got->adapter, $got->getFilesystem()->getAdapter());
    }

    public function testStat()
    {
        $got = $this->getMockForTrait(GenericOperationTrait::class, [], '', true, true, true, [
            'getPath',
        ]);

        $got->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('foo.bar'));

        $promise = \React\Promise\resolve();

        $got->adapter = $this->mockAdapter();
        $got->adapter->expects($this->once())
            ->method('stat')
            ->with('foo.bar')
            ->will($this->returnValue($promise));

        $got->filesystem = Filesystem::createFromAdapter($got->adapter);

        $this->assertSame($promise, $got->stat());
    }

    public function testChmod()
    {
        $got = $this->getMockForTrait(GenericOperationTrait::class, [], '', true, true, true, [
            'getPath',
        ]);

        $got->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('foo.bar'));

        $promise = \React\Promise\resolve();

        $got->adapter = $this->mockAdapter();
        $got->adapter->expects($this->once())
            ->method('chmod')
            ->with('foo.bar', 'abc')
            ->will($this->returnValue($promise));

        $got->filesystem = Filesystem::createFromAdapter($got->adapter);

        $this->assertSame($promise, $got->chmod('abc'));
    }

    public function testChown()
    {
        $got = $this->getMockForTrait(GenericOperationTrait::class, [], '', true, true, true, [
            'getPath',
        ]);

        $got->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('foo.bar'));

        $promise = \React\Promise\resolve();

        $got->adapter = $this->mockAdapter();
        $got->adapter->expects($this->once())
            ->method('chown')
            ->with('foo.bar', 1, 2)
            ->will($this->returnValue($promise));

        $got->filesystem = Filesystem::createFromAdapter($got->adapter);

        $this->assertSame($promise, $got->chown(1, 2));
    }

    public function testChownDefaults()
    {
        $got = $this->getMockForTrait(GenericOperationTrait::class, [], '', true, true, true, [
            'getPath',
        ]);

        $got->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('foo.bar'));

        $promise = \React\Promise\resolve();

        $got->adapter = $this->mockAdapter();
        $got->adapter->expects($this->once())
            ->method('chown')
            ->with('foo.bar', -1, -1)
            ->will($this->returnValue($promise));

        $got->filesystem = Filesystem::createFromAdapter($got->adapter);

        $this->assertSame($promise, $got->chown());
    }

    public function testCreateNameNParentFromFilename()
    {
        $ds = DIRECTORY_SEPARATOR;
        $node = new File(
            $ds.'foo'.$ds.'bar'.$ds.'baz'.$ds.'rabbit'.$ds.'kitten'.$ds.'index.php',
            Filesystem::createFromAdapter($this->mockAdapter())
        );

        foreach ([
            [
                'index.php',
                $ds.'foo'.$ds.'bar'.$ds.'baz'.$ds.'rabbit'.$ds.'kitten'.$ds.'index.php',
            ],
            [
                'kitten',
                $ds.'foo'.$ds.'bar'.$ds.'baz'.$ds.'rabbit'.$ds.'kitten'.$ds.'',
            ],
            [
                'rabbit',
                $ds.'foo'.$ds.'bar'.$ds.'baz'.$ds.'rabbit'.$ds.'',
            ],
            [
                'baz',
                $ds.'foo'.$ds.'bar'.$ds.'baz'.$ds.'',
            ],
            [
                'bar',
                $ds.'foo'.$ds.'bar'.$ds.'',
            ],
            [
                'foo',
                $ds.'foo'.$ds.'',
            ],
            [
                '',
                $ds.'',
            ],
        ] as $names) {
            $this->assertSame($names[0], $node->getName());
            $this->assertSame($names[1], $node->getPath());
            $node = $node->getParent();
        }
    }
}
