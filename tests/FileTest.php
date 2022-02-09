<?php

namespace React\Tests\Filesystem;

use React\EventLoop\LoopInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Node\NotExistInterface;
use React\Filesystem\Stat;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use function Clue\React\Block\await;
use function React\Promise\all;

final class FileTest extends AbstractFilesystemTestCase
{
    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function stat(AdapterInterface $filesystem): void
    {
        $stat = $this->await($filesystem->detect(__FILE__)->then(static function (FileInterface $file): PromiseInterface {
            return $file->stat();
        }));

        self::assertInstanceOf(Stat::class, $stat);
        self::assertSame(filesize(__FILE__), $stat->size());
    }

    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function getContents(AdapterInterface $filesystem): void
    {
        $fileContents = $this->await($filesystem->detect(__FILE__)->then(static function (FileInterface $file): PromiseInterface {
            return $file->getContents();
        }));

        self::assertSame(file_get_contents(__FILE__), $fileContents);
    }

    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function getContents34and5thCharacterFromFile(AdapterInterface $filesystem): void
    {
        $directoryName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . bin2hex(random_bytes(13)) . DIRECTORY_SEPARATOR;
        $fileName = $directoryName . bin2hex(random_bytes(13));
        mkdir($directoryName);
        \file_put_contents($fileName, 'abcdefghijklmnopqrstuvwxyz');
        $fileContents = $this->await($filesystem->detect($fileName)->then(static function (FileInterface $file): PromiseInterface {
            return $file->getContents(3, 3);
        }));

        self::assertSame('def', $fileContents);
    }

    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function putContents(AdapterInterface $filesystem): void
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . bin2hex(random_bytes(13)) . DIRECTORY_SEPARATOR . bin2hex(random_bytes(9));
        $fileContents = bin2hex(random_bytes(128));

        $writtenLength = $this->await($filesystem->detect($fileName)->then(static fn (NotExistInterface $notExist): PromiseInterface => $notExist->createFile())->then(function (FileInterface $file) use ($fileContents): PromiseInterface {
            return $file->putContents($fileContents);
        }));

        self::assertSame($writtenLength, strlen(file_get_contents($fileName)));
        self::assertSame($fileContents, file_get_contents($fileName));

        unlink($fileName);
    }

    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function putContentsMultipleBigFiles(AdapterInterface $filesystem): void
    {
        $directoryName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . bin2hex(random_bytes(13)) . DIRECTORY_SEPARATOR;
        $this->await($filesystem->detect($directoryName)->then(static fn(NotExistInterface $notExist): PromiseInterface => $notExist->createDirectory()));
        $fileNames = [];
        $fileContents = [];
        for ($i = 0; $i < 25; $i++) {
            $fileNames[] = $directoryName . bin2hex(random_bytes(13));
        }
        foreach ($fileNames as $fileName) {
            $fileContents[$fileName] = bin2hex(random_bytes(4096));
            touch($fileName);
        }

        $promises = [];
        foreach ($fileContents as $fileName => $fileContent) {
            $promises[$fileName] = $filesystem->detect($fileName)->then(static function (FileInterface $file) use ($fileContent): PromiseInterface {
                return $file->putContents($fileContent);
            });
        }

        $writtenLengths = $this->await(all($promises));

        foreach ($writtenLengths as $fileName => $writtenLength) {
            self::assertSame($writtenLength, strlen(file_get_contents($fileName)));
            self::assertSame($fileContents[$fileName], file_get_contents($fileName));
            unlink($fileName);
        }
    }

    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function putContentsAppend(AdapterInterface $filesystem): void
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . bin2hex(random_bytes(13)) . DIRECTORY_SEPARATOR . bin2hex(random_bytes(9));
        $fileContentsFirst = bin2hex(random_bytes(128));
        $fileContentsSecond = bin2hex(random_bytes(128));
        $writtenLengthFirst = $this->await($filesystem->detect($fileName)->then(static fn (NotExistInterface $notExist): PromiseInterface => $notExist->createFile())->then(static function (FileInterface $file) use ($fileContentsFirst): PromiseInterface {
            return $file->putContents($fileContentsFirst);
        }));

        self::assertSame($writtenLengthFirst, strlen(file_get_contents($fileName)));
        self::assertSame($fileContentsFirst, file_get_contents($fileName));

        $writtenLengthSecond = $this->await($filesystem->detect($fileName)->then(static function (FileInterface $file) use ($fileContentsSecond): PromiseInterface {
            return $file->putContents($fileContentsSecond, \FILE_APPEND);
        }));

        self::assertSame($writtenLengthFirst + $writtenLengthSecond, strlen(file_get_contents($fileName)));
        self::assertSame($fileContentsFirst . $fileContentsSecond, file_get_contents($fileName));

        unlink($fileName);
    }

    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function putContentsAppendBigFile(AdapterInterface $filesystem): void
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . bin2hex(random_bytes(13)) . DIRECTORY_SEPARATOR . bin2hex(random_bytes(9));
        $this->await($filesystem->detect($fileName)->then(static fn(NotExistInterface $notExist): PromiseInterface => $notExist->createFile()));

        $fileContents = [];
        $writtenLength = 0;
        for ($i = 0; $i < 13; $i++) {
            $fileContents[] = bin2hex(random_bytes(4096));
        }

        foreach ($fileContents as $fileContent) {
            $writtenLength += $this->await($filesystem->detect($fileName)->then(static function (FileInterface $file) use ($fileContent): PromiseInterface {
                return $file->putContents($fileContent, \FILE_APPEND);
            }));
        }

        self::assertSame($writtenLength, strlen(file_get_contents($fileName)));
        self::assertSame(implode('', $fileContents), file_get_contents($fileName));

        unlink($fileName);
    }

    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function putContentsAppendMultipleBigFiles(AdapterInterface $filesystem): void
    {
        $this->runMultipleFilesTests($filesystem, 8, 4096, 4);
    }

    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function putContentsAppendLotsOfSmallFiles(AdapterInterface $filesystem): void
    {
        $this->runMultipleFilesTests($filesystem, 16, 16, 4);
    }

    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function putContentsAppendLoadsOfSmallFiles(AdapterInterface $filesystem): void
    {
        $this->runMultipleFilesTests($filesystem, 32, 8, 8);
    }

    public function runMultipleFilesTests(AdapterInterface $filesystem, int $fileCount, int $fileSize, int $chunkCount): void
    {
        $directoryName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . bin2hex(random_bytes(13)) . DIRECTORY_SEPARATOR;
        mkdir($directoryName, 0777, true);
        $fileNames = [];
        $fileContents = [];
        for ($i = 0; $i < $fileCount; $i++) {
            $fileNames[] = $directoryName . bin2hex(random_bytes(13));
        }
        foreach ($fileNames as $fileName) {
            $fileContents[$fileName] = [];
            touch($fileName);
        }

        foreach ($fileNames as $fileName) {
            for ($i = 0; $i < $chunkCount; $i++) {
                $fileContents[$fileName][] = bin2hex(random_bytes($fileSize));
            }
        }

        $promises = [];
        foreach ($fileContents as $fileName => $fileContent) {
            $queue = new \SplQueue();
            foreach ($fileContent as $chunk) {
                $queue->enqueue($chunk);
            }
            $promises[$fileName] = $filesystem->detect($fileName)->then(static function (FileInterface $file) use ($queue): PromiseInterface {
                return new Promise(function (callable $resolve, callable $reject) use ($queue, $file): void {
                    $bytesWritten = 0;
                    $writeFunction = function () use (&$writeFunction, &$bytesWritten, $queue, $file, $resolve, $reject) {
                        if ($queue->count() > 0) {
                            $file->putContents($queue->dequeue(), \FILE_APPEND)->then(function (int $writtenBytes) use (&$writeFunction, &$bytesWritten): void {
                                $bytesWritten += $writtenBytes;
                                $writeFunction();
                            }, $reject);
                            return;
                        }

                        $resolve($bytesWritten);
                    };
                    $writeFunction();
                });
            });
        }

        $writtenLengths = $this->await(all($promises));

        foreach ($writtenLengths as $fileName => $writtenLength) {
            self::assertSame($writtenLength, strlen(file_get_contents($fileName)));
            self::assertSame(implode('', $fileContents[$fileName]), file_get_contents($fileName));
            unlink($fileName);
        }
    }

    /**
     * @test
     *
     * @dataProvider provideFilesystems
     */
    public function unlink(AdapterInterface $filesystem): void
    {
        $fileName = __FILE__ . '.' . time();
        $fileContents = bin2hex(random_bytes(2048));
        file_put_contents($fileName, $fileContents);
        self::assertFileExists($fileName);
        $this->await($filesystem->detect($fileName)->then(static function (FileInterface $file): PromiseInterface {
            return $file->unlink();
        }));


        self::assertFileDoesNotExist($fileName);
    }
}
