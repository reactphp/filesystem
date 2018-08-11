<?php

namespace React\Tests\Filesystem;

use React\Filesystem\Filesystem;
use React\Filesystem\QueuedInvoker;
use React\Filesystem\PooledInvoker;
use React\Filesystem\InstantInvoker;
use React\Filesystem\AdapterInterface;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\CallInvokerInterface;
use React\Filesystem\ThrottledQueuedInvoker;
use React\Filesystem\Node\NodeInterface;

class FilesystemTest extends TestCase
{
    public function filesystemProvider()
    {
        $adapter = $this->mockAdapter();
        $fs = Filesystem::createFromAdapter($adapter);

        $adapters = [
            $this->pooled($fs, $adapter),
            $this->instant($fs, $adapter),
            $this->queued($fs, $adapter),
            $this->throttledqueued($fs, $adapter),
        ];
    
        return $adapters;
    }

    protected function pooled($fs, $adapter)
    {
        $invoker = new PooledInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$fs, $adapter, $invoker];
    }

    protected function instant($fs, $adapter)
    {
        $invoker = new InstantInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$fs, $adapter, $invoker];
    }

    protected function queued($fs, $adapter)
    {
        $invoker = new QueuedInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$fs, $adapter, $invoker];
    }

    protected function throttledqueued($fs, $adapter)
    {
        $invoker = new ThrottledQueuedInvoker($adapter);
        $adapter->setInvoker($invoker);

        return [$fs, $adapter, $invoker];
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testGetAdapter(FilesystemInterface $filesystem)
    {
        $this->assertInstanceOf(AdapterInterface::class, $filesystem->getAdapter());
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testFile(FilesystemInterface $filesystem)
    {
        $this->assertInstanceOf(NodeInterface::class, $filesystem->file('foo.bar'));
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testDir(FilesystemInterface $filesystem)
    {
        $this->assertInstanceOf(NodeInterface::class, $filesystem->dir('foo'));
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testSetInvoker(FilesystemInterface $filesystem, AdapterInterface $adapter, CallInvokerInterface $invoker)
    {
        $invoker2 = new InstantInvoker($adapter);
        $filesystem->setInvoker($invoker2);
        $this->assertSame($invoker2, $adapter->getInvoker());
        $filesystem->setInvoker($invoker);
    }
}
