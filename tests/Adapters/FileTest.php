<?php

namespace React\Tests\Filesystem\Adapters;

use Clue\React\Block;
use React\Filesystem\ChildProcess;
use React\Filesystem\Eio;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\Pthreads;

class FileTest extends AbstractAdaptersTest
{
    /**
     * @dataProvider filesystemProvider
     */
    public function _testStat(FilesystemInterface $filesystem)
    {
        $actualStat = lstat(__FILE__);
        $result = Block\await($filesystem->file(__FILE__)->stat(), $this->loop);
        $this->assertSame($actualStat, $result);
    }
}
