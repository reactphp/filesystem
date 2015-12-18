<?php

namespace React\Tests\Filesystem\ChildProcess;

use React\Filesystem\ChildProcess\Adapter;
use React\Tests\Filesystem\TestCase;

/**
 * @requires extension eio
 */
class AdapterTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            'React\Filesystem\AdapterInterface',
            new Adapter($this->getMock('React\EventLoop\LoopInterface'), [
                'pool' => [
                    'class' => 'WyriHaximus\React\ChildProcess\Pool\DummyPool',
                ],
            ])
        );
    }

    public function testGetLoop()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $filesystem = new Adapter($loop, [
            'pool' => [
                'class' => 'WyriHaximus\React\ChildProcess\Pool\DummyPool',
            ],
        ]);
        $this->assertSame($loop, $filesystem->getLoop());
    }
}
