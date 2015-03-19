<?php

namespace React\Tests\Filesystem\Node;

trait NodeTestTrait
{
    public function test__toString()
    {
        $nodeClass = $this->getNodeClass();
        $this->assertSame('foo.bar', (string) (new $nodeClass('foo.bar', $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]))));
    }

    abstract function getNodeClass();
}
