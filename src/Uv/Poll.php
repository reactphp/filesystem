<?php

namespace React\Filesystem\Uv;

use React\EventLoop\ExtUvLoop;
use React\EventLoop\TimerInterface;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\PollInterface;
use React\Filesystem\Stat;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use RuntimeException;
use React\EventLoop\LoopInterface;
use React\Filesystem\Node;
use UV;
use function React\Promise\all;

final class Poll implements PollInterface
{
    private LoopInterface $loop;
    private int $workInProgress = 0;
    private ?TimerInterface $workInProgressTimer = null;
    private int $workInterval = 10;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function activate(): void
    {
        if ($this->workInProgress++ === 0) {
            $this->workInProgressTimer = $this->loop->addPeriodicTimer($this->workInterval, static function () {});
        }
    }

    public function deactivate(): void
    {
        if (--$this->workInProgress <= 0) {
            $this->loop->cancelTimer($this->workInProgressTimer);
        }
    }
}
