<?php

namespace React\Tests\Filesystem\Stream;

use React\Filesystem\Eio\ConstTypeDetector;
use React\Filesystem\Filesystem;

class ConstTypeDetectorTest extends \PHPUnit_Framework_TestCase
{
    public function providerDetect()
    {
        return [
            [
                EIO_DT_DIR,
                'dir',
            ],
            [
                EIO_DT_REG,
                'file',
            ],
        ];
    }

    /**
     * @dataProvider providerDetect
     */
    public function testDetect($const, $method)
    {
        $callbackFired = false;

        $filesystem = Filesystem::create($this->getMock('React\EventLoop\StreamSelectLoop'));
        (new ConstTypeDetector($filesystem))->detect([
            'type' => $const,
        ])->then(function ($result) use ($filesystem, $method, &$callbackFired) {
            $this->assertSame([
                $filesystem,
                $method,
            ], $result);
            $callbackFired = true;
        });

        $this->assertTrue($callbackFired);
    }

    public function testDetectUnknown()
    {
        $callbackFired = false;

        $filesystem = Filesystem::create($this->getMock('React\EventLoop\StreamSelectLoop'));
        (new ConstTypeDetector($filesystem))->detect([
            'type' => 123,
        ])->otherwise(function ($result) use (&$callbackFired) {
            $this->assertSame(null, $result);
            $callbackFired = true;
        });

        $this->assertTrue($callbackFired);
    }
}
