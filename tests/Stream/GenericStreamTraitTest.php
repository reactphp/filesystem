<?php

namespace React\Tests\Filesystem\Stream;

class GenericStreamTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testGetFilesystem()
    {
        $gst = $this->getMockForTrait('React\Filesystem\Stream\GenericStreamTrait', [
            'foo.bar',
            'abc',
            $this->getMock('React\Filesystem\EioAdapter', [], [
                $this->getMock('React\EventLoop\StreamSelectLoop'),
            ]),
        ]);

        $this->assertSame('abc', $gst->getFiledescriptor());
    }
}
