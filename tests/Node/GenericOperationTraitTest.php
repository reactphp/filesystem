<?php

namespace React\Tests\Filesystem\Node;

use React\Filesystem\Filesystem;
use React\Filesystem\Node\File;
use React\Promise\FulfilledPromise;
use React\Tests\Filesystem\TestCase;

class GenericOperationTraitTest extends TestCase
{

    public function testGetFilesystem()
    {
        $got = $this->getMockForTrait('React\Filesystem\Node\GenericOperationTrait');

        $got->adapter = $this->mockAdapter();

        $got->filesystem = Filesystem::createFromAdapter($got->adapter);

        $this->assertSame($got->filesystem, $got->getFilesystem());
        $this->assertSame($got->adapter, $got->getFilesystem()->getAdapter());
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
        $got = $this->getMockForTrait('React\Filesystem\Node\GenericOperationTrait', [], '', true, true, true, [
            'getPath',
        ]);
        $got->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('foo.bar'));

        $promise = new FulfilledPromise();

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
        $got = $this->getMockForTrait('React\Filesystem\Node\GenericOperationTrait', [], '', true, true, true, [
            'getPath',
        ]);
        $got->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('foo.bar'));

        $promise = new FulfilledPromise();

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
        $got = $this->getMockForTrait('React\Filesystem\Node\GenericOperationTrait', [], '', true, true, true, [
            'getPath',
        ]);
        $got->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('foo.bar'));

        $promise = new FulfilledPromise();

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
        $path = $this->concatPath('', 'foo', 'bar', 'baz', 'rabbit', 'kitten', 'index.php');
        $node = new File($path, Filesystem::createFromAdapter($this->mockAdapter()));

        foreach ([
            [
                'index.php',
                $path,
            ],
            [
                'kitten',
                $this->concatPath('', 'foo', 'bar', 'baz', 'rabbit', 'kitten', ''),
            ],
            [
                'rabbit',
                $this->concatPath('', 'foo', 'bar', 'baz', 'rabbit', ''),
            ],
            [
                'baz',
                $this->concatPath('', 'foo', 'bar', 'baz', ''),
            ],
            [
                'bar',
                $this->concatPath('', 'foo', 'bar', ''),
            ],
            [
                'foo',
                $this->concatPath('', 'foo', ''),
            ],
            [
                '',
                \DIRECTORY_SEPARATOR,
            ],
        ] as $names) {
            $this->assertSame($names[0], $node->getName());
            $this->assertSame($names[1], $node->getPath());
            $node = $node->getParent();
        }
    }
}
