<?php

namespace React\Tests\Filesystem;

use React\Filesystem\OpenFileLimiter;

class FunctionsTest extends TestCase
{
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
