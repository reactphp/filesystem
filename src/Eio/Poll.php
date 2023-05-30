<?php

namespace React\Filesystem\Eio;

use React\EventLoop\LoopInterface;
use React\Filesystem\PollInterface;

final class Poll implements PollInterface
{
    private LoopInterface $loop;
    private $fd;
    private \Closure $handleEvent;
    private int $workInProgress = 0;

    public function __construct(LoopInterface $loop)
    {
        $this->fd = EventStream::get();
        $this->loop = $loop;
        $this->handleEvent = function () {
            $this->handleEvent();
        };
    }

    public function activate(): void
    {
        if ($this->workInProgress++ === 0) {
            $this->loop->addReadStream($this->fd, $this->handleEvent);
        }
    }

    private function handleEvent()
    {
        while (eio_npending()) {
            eio_poll();
        }
    }

    public function deactivate(): void
    {
        if (--$this->workInProgress <= 0) {
            $this->loop->removeReadStream($this->fd);
        }
    }
}
