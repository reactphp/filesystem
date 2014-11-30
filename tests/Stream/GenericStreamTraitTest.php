<?php

namespace React\Tests\Filesystem\Stream;

class GenericStreamTraitTest extends \PHPUnit_Framework_TestCase
{

    public function testGetFilesystem()
    {
        $gst = $this->getMockForTrait('React\Filesystem\Stream\GenericStreamTrait');

        $gst->fileDescriptor = 'abc';

        $this->assertSame($gst->fileDescriptor, $gst->getFiledescriptor());
    }
}
