<?php

namespace React\Tests\Filesystem\Uv;

use React\Filesystem\Uv\OpenFlagResolver;
use React\Tests\Filesystem\AbstractFlagResolverTest;

/**
 * @requires extension uv
 */
class OpenFlagResolverTest extends AbstractFlagResolverTest
{
    /**
     * @var OpenFlagResolver
     */
    protected $resolver;

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
