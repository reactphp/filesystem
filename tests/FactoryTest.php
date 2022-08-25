<?php

namespace React\Tests\Filesystem;

use React\EventLoop;
use React\EventLoop\LoopInterface;
use React\Filesystem\Factory;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Node\DirectoryInterface;
use React\Filesystem\Node\FileInterface;
use function React\Async\await;

final class FactoryTest extends AbstractFilesystemTestCase
{
    /**
     * @test
     */
    public function factory(): void
    {
        $node = await(Factory::create()->detect(__FILE__));

        self::assertInstanceOf(FileInterface::class, $node);
    }
}