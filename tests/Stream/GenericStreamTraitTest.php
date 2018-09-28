<?php

namespace React\Tests\Filesystem\Stream;

use React\Filesystem\Stream\GenericStreamTrait;

use React\Tests\Filesystem\TestCase;

class GenericStreamTraitTest extends TestCase
{
    public function testGetFilesystem()
    {
        $gst = $this->getMockForTrait(GenericStreamTrait::class, [
            'foo.bar',
            'abc',
            $this->mockAdapter(),
        ]);

        $this->assertSame('abc', $gst->getFiledescriptor());
    }

    public function testSetClosed()
    {
        $gst = $this->getMockForTrait(GenericStreamTrait::class, [
            'foo.bar',
            'abc',
            $this->mockAdapter(),
        ]);

        $gst->setClosed(true);
        $this->assertSame(true, $gst->isClosed());
    }

    public function testSetPath()
    {
        $gst = $this->getMockForTrait(GenericStreamTrait::class, [
            'foo.bar',
            'abc',
            $this->mockAdapter(),
        ]);

        $gst->setPath('foobar');
        $this->assertSame('foobar', $gst->getPath());
    }
}
