<?php

namespace React\Tests\Filesystem;

use Exception;
use React\Filesystem\Filesystem;
use React\Filesystem\ModeTypeDetector;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Node\DirectoryInterface;

class ModeTypeDetectorTest extends TestCase
{
    public function providerDetect()
    {
        return [
            [
                0x4000,
                DirectoryInterface::class,
            ],
            [
                0x8000,
                FileInterface::class,
            ],
        ];
    }

    /**
     * @dataProvider providerDetect
     */
    public function testDetect($mode, $method)
    {
        $adapter = $this->mockAdapter();
        $filesystem = Filesystem::createFromAdapter($adapter);

        $promise = (new ModeTypeDetector($filesystem))->detect([
            'path' => 'foo.bar',
            'mode' => $mode,
        ]);
        $result = $this->await($promise, $adapter->getLoop());

        $this->assertInstanceof($method, $result);
    }

    public function testDetectUnknown()
    {
        $adapter = $this->mockAdapter();
        $filesystem = Filesystem::createFromAdapter($adapter);

        $promise = (new ModeTypeDetector($filesystem))->detect([
            'mode' => 0,
            'path' => 'foo.bar',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown mode');

        $this->await($promise, $adapter->getLoop());
    }
}
