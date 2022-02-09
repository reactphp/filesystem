<?php

namespace React\Tests\Filesystem;

use React\EventLoop\LoopInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Node\DirectoryInterface;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Node\NotExistInterface;
use function Clue\React\Block\await;

final class FilesystemTest extends AbstractFilesystemTestCase
{
    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function file(AdapterInterface $filesystem): void
    {
        $node = $this->await($filesystem->detect(__FILE__));

        self::assertInstanceOf(FileInterface::class, $node);
    }

    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function directory(AdapterInterface $filesystem): void
    {
        $node = $this->await($filesystem->detect(__DIR__));

        self::assertInstanceOf(DirectoryInterface::class, $node);
    }

    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function notExists(AdapterInterface $filesystem): void
    {
        $node = $this->await($filesystem->detect(bin2hex(random_bytes(13))));

        self::assertInstanceOf(NotExistInterface::class, $node);
    }
}
