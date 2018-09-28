<?php

namespace React\Tests\Filesystem;

use Exception;
use React\Filesystem\Filesystem;
use React\Filesystem\MappedTypeDetector;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Node\DirectoryInterface;

class MappedTypeDetectorTest extends TestCase
{
    public function providerDetect()
    {
        return [
            [
                'dir',
                DirectoryInterface::class,
            ],
            [
                'file',
                FileInterface::class,
            ],
        ];
    }

    /**
     * @dataProvider providerDetect
     */
    public function testDetect($type, $class)
    {
        $adapter = $this->mockAdapter();
        $filesystem = Filesystem::createFromAdapter($adapter);

        $promise = (MappedTypeDetector::createDefault($filesystem))->detect([
            'path' => 'foo.bar',
            'type' => $type,
        ]);
        $result = $this->await($promise, $adapter->getLoop());

        $this->assertInstanceof($class, $result);
    }

    public function testDetectUnknown()
    {
        $adapter = $this->mockAdapter();
        $filesystem = Filesystem::createFromAdapter($adapter);

        $promise = (MappedTypeDetector::createDefault($filesystem))->detect([
            'mode' => 0,
            'path' => 'foo.bar',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown type');

        $this->await($promise, $adapter->getLoop());
    }
}
