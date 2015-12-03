<?php

namespace React\Tests\Filesystem\Eio;

use React\Filesystem\Eio\OpenFlagResolver;
use React\Tests\Filesystem\AbstractFlagResolverTest;

/**
 * @requires extension eio
 */
class OpenFlagResolverTest extends AbstractFlagResolverTest
{
    public function setUp()
    {
        parent::setUp();
        $this->resolver = new OpenFlagResolver();
    }

    public function tearDown()
    {
        unset($this->resolver);
        parent::tearDown();
    }


    public function testDefaultFlags()
    {
        $this->assertSame(OpenFlagResolver::DEFAULT_FLAG, $this->resolver->defaultFlags());
    }
}
