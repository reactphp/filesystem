<?php

namespace React\Tests\Filesystem;

abstract class AbstractFlagResolverTest extends TestCase
{
    public function testInheritance()
    {
        $this->assertInstanceOf('React\Filesystem\FlagResolver', $this->resolver);
        $this->assertInstanceOf('React\Filesystem\FlagResolverInterface', $this->resolver);
    }

    public function testFlagMappingType()
    {
        $this->assertInternalType('array', $this->resolver->flagMapping());
    }
}
