<?php

namespace React\Tests\Filesystem\Eio;

use Exception;
use React\Filesystem\Eio\Adapter;
use React\Filesystem\Eio\ConstTypeDetector;
use React\Filesystem\Filesystem;
use React\Filesystem\Node\Directory;
use React\Filesystem\Node\File;
use React\Tests\Filesystem\TestCase;

/**
 * @requires extension eio
 */
class ConstTypeDetectorTest extends TestCase
{
    public function providerDetect()
    {
        return [
            [
                EIO_DT_DIR,
                Directory::class,
            ],
            [
                EIO_DT_REG,
                File::class,
            ],
        ];
    }

    /**
     * @dataProvider providerDetect
     */
    public function testDetect($const, $method)
    {
        $adapter = new Adapter($this->loop);
        $filesystem = Filesystem::createFromAdapter($adapter);

        $result = $this->await((new ConstTypeDetector($filesystem))->detect([
            'path' => 'foo.bar',
            'type' => $const,
        ]), $this->loop);

        $this->assertInstanceOf($method, $result);
    }

    public function testDetectUnknown()
    {
        $adapter = new Adapter($this->loop);
        $filesystem = Filesystem::createFromAdapter($adapter);

        $this->expectException(Exception::class);
        $this->await((new ConstTypeDetector($filesystem))->detect([
            'type' => 123,
        ]), $this->loop);
    }
}
