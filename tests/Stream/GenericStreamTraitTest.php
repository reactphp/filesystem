<?php

namespace React\Tests\Filesystem\Stream;

use React\Tests\Filesystem\TestCase;

class GenericStreamTraitTest extends TestCase
{
    public function testGetFilesystem()
    {
        $gst = $this->getMockForTrait('React\Filesystem\Stream\GenericStreamTrait', [
            'foo.bar',
            'abc',
            $this->mockAdapter(),
        ]);

        $this->assertSame('abc', $gst->getFiledescriptor());
    }
}
