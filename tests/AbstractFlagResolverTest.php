<?php

namespace React\Tests\Filesystem;

abstract class AbstractFlagResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testInheritance()
    {
        $this->assertInstanceOf('React\Filesystem\FlagResolver', $this->resolver);
        $this->assertInstanceOf('React\Filesystem\FlagResolverInterface', $this->resolver);
    }

    public function testDefaultFlagsType()
    {
        $this->assertInternalType('int', $this->resolver->defaultFlags());
    }

    public function testFlagMappingType()
    {
        $this->assertInternalType('array', $this->resolver->flagMapping());
    }
}
