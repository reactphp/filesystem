<?php

namespace React\Tests\Filesystem;

use React\EventLoop\LoopInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Node\DirectoryInterface;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Stat;
use React\Promise\PromiseInterface;
use function React\Async\await;

final class DirectoryTest extends AbstractFilesystemTestCase
{
    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function stat(AdapterInterface $filesystem): void
    {
        $stat = await($filesystem->detect(__DIR__)->then(static function (DirectoryInterface $directory): PromiseInterface {
            return $directory->stat();
        }));

        self::assertInstanceOf(Stat::class, $stat);
        self::assertSame(stat(__DIR__)['size'], $stat->size());
    }

    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function ls(AdapterInterface $filesystem): void
    {
        $expectedListing = [];

        $d = dir(__DIR__);
        while (false !== ($entry = $d->read())) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $expectedListing[__DIR__ . DIRECTORY_SEPARATOR . $entry] = is_file(__DIR__ . DIRECTORY_SEPARATOR . $entry) ? FileInterface::class : DirectoryInterface::class;
        }
        $d->close();

        ksort($expectedListing);

        $directoryListing = await($filesystem->detect(__DIR__)->then(static function (DirectoryInterface $directory): PromiseInterface {
            return $directory->ls();
        }));

        $listing = [];
        foreach ($directoryListing as $node) {
            $listing[$node->path() . $node->name()] = $node instanceof FileInterface ? FileInterface::class : DirectoryInterface::class;
        }
        ksort($listing);

        self::assertSame($expectedListing, $listing);
    }
}
