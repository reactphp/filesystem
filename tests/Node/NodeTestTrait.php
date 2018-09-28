<?php

namespace React\Tests\Filesystem\Node;

use React\Filesystem\Filesystem;

trait NodeTestTrait
{
    abstract public function providerToString();

    /**
     * @dataProvider providerToString
     */
    public function test__toString($in, $out)
    {
        $nodeClass = $this->getNodeClass();
        $this->assertSame($out, (string) (new $nodeClass($in, Filesystem::createFromAdapter($this->mockAdapter()))));
    }

    abstract protected function getNodeClass();
}
