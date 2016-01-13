<?php

namespace React\Tests\Filesystem;

use React\Filesystem\OpenFileLimiter;

class FunctionsTest extends TestCase
{
    public function testGetInvoker()
    {
        $adapter = $this->getMock('React\Filesystem\AdapterInterface');
        $callInvoker = $this->getMock('React\Filesystem\CallInvokerInterface');
        $key = 'k';
        $options = [
            $key => $callInvoker,
        ];
        $fallback = '';
        $this->assertSame($callInvoker, \React\Filesystem\getInvoker($adapter, $options, $key, $fallback));
    }

    public function testGetInvokerFallback()
    {
        $adapter = $this->getMock('React\Filesystem\AdapterInterface');
        $key = 'k';
        $options = [];
        $fallback = '\stdClass';
        $this->assertInstanceOf($fallback, \React\Filesystem\getInvoker($adapter, $options, $key, $fallback));
    }

    public function testGetOpenFileLimit()
    {
        $limit = 123;
        $this->assertSame($limit, \React\Filesystem\getOpenFileLimit([
            'open_file_limit' => $limit,
        ]));
    }
    public function testGetOpenFileLimitFallback()
    {
        $this->assertSame(OpenFileLimiter::DEFAULT_LIMIT, \React\Filesystem\getOpenFileLimit([]));
    }
}
