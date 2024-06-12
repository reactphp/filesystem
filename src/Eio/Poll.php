<?php

namespace React\Filesystem\Eio;

use React\EventLoop\Loop;
use React\Filesystem\PollInterface;

/**
 * @internal
 */
final class Poll implements PollInterface
{
    private $fd;
    private \Closure $handleEvent;
    private int $workInProgress = 0;

    public function __construct()
    {
        $this->fd = EventStream::get();
        $this->handleEvent = function () {
            $this->handleEvent();
        };
    }

    public function activate(): void
    {
        if ($this->workInProgress++ === 0) {
            Loop::addReadStream($this->fd, $this->handleEvent);
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
            Loop::removeReadStream($this->fd);
        }
    }
}
