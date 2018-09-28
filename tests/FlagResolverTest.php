<?php

namespace React\Tests\Filesystem;

use React\Filesystem\FlagResolver;

class FlagResolverTest extends TestCase
{
    public function testResolve()
    {
        $resolver = $this->getMockBuilder(FlagResolver::class)
            ->setMethods([
                'defaultFlags',
                'flagMapping',
            ])
            ->getMock();

        $resolver
            ->expects($this->once())
            ->method('defaultFlags')
            ->with()
            ->will($this->returnValue(0));

        $resolver
            ->expects($this->once())
            ->method('flagMapping')
            ->with()
            ->will($this->returnValue([
                'b' => 1,
                'a' => 2,
                'r' => 4,
            ]));

        $this->assertSame(7, $resolver->resolve('bar'));
    }
}
