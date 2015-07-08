<?php

namespace React\Tests\Filesystem\Node;

trait NodeTestTrait
{
    abstract public function providerToString();

    /**
     * @dataProvider providerToString
     */
    public function test__toString($in, $out)
    {
        $nodeClass = $this->getNodeClass();
        $this->assertSame($out, (string) (new $nodeClass($in, $this->getMock('React\Filesystem\EioAdapter', [], [
            $this->getMock('React\EventLoop\StreamSelectLoop'),
        ]))));
    }

    abstract protected function getNodeClass();
}
