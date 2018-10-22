<?php

namespace React\Tests\Filesystem;

use Exception;
use React\Filesystem\Filesystem;
use React\Filesystem\ModeTypeDetector;
use React\Promise\FulfilledPromise;

class ModeTypeDetectorTest extends TestCase
{
    public function providerDetect()
    {
        return [
            [
                0x4000,
                'dir',
            ],
            [
                0x8000,
                'file',
            ],
        ];
    }

    /**
     * @dataProvider providerDetect
     */
    public function testDetect($mode, $method)
    {
        $callbackFired = false;

        $adapter = $this->mockAdapter();
        $adapter
            ->expects($this->any())
            ->method('stat')
            ->with('foo.bar')
            ->will($this->returnValue(new FulfilledPromise([
                'mode' => $mode,
            ])))
        ;
        $filesystem = Filesystem::createFromAdapter($adapter);
        (new ModeTypeDetector($filesystem))->detect([
            'path' => 'foo.bar',
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

        $adapter = $this->mockAdapter();
        $adapter
            ->expects($this->any())
            ->method('stat')
            ->with('foo.bar')
            ->will($this->returnValue(new FulfilledPromise([
                'mode' => 0x3000,
            ])))
        ;
        $filesystem = Filesystem::createFromAdapter($adapter);
        (new ModeTypeDetector($filesystem))->detect([
            'path' => 'foo.bar',
        ])->otherwise(function ($result) use (&$callbackFired) {
            $this->assertInstanceOf('Exception', $result);
            $callbackFired = true;
        });

        $this->assertTrue($callbackFired);
    }
}
