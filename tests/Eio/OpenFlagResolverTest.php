<?php

namespace React\Tests\Filesystem\Eio;

use React\Filesystem\Eio\OpenFlagResolver;

class OpenFlagResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testInheritance()
    {
        $this->assertInstanceOf('React\Filesystem\FlagResolver', new OpenFlagResolver());
    }

    public function testDefaultFlags()
    {
        $this->assertSame(OpenFlagResolver::DEFAULT_FLAG, (new OpenFlagResolver())->defaultFlags());
    }

    public function testFlagMapping()
    {
        $this->assertInternalType('array', (new OpenFlagResolver())->flagMapping());
    }
}
