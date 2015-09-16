<?php

namespace React\Filesystem;

use React\Promise\Deferred;
use React\Promise\FulfilledPromise;

class OpenFileLimiter
{
    /**
     * @var int
     */
    protected $limit = 0;

    /**
     * @var int
     */
    protected $current = 0;

    /**
     * @var \SplQueue
     */
    protected $promises;

    /**
     * @param int $limit
     */
    public function __construct($limit = 512)
    {
        $this->limit = $limit;
        $this->promises = new \SplQueue();
    }

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function open()
    {
        $this->current++;

        if ($this->current <= $this->limit) {
            return new FulfilledPromise();
        }

        $deferred = new Deferred();
        $this->promises->enqueue($deferred);
        return $deferred->promise();
    }

    public function close()
    {
        $this->current--;
        if (!$this->promises->isEmpty()) {
            $this->promises->dequeue()->resolve();
        }
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOutstanding()
    {
        return $this->current;
    }

    /**
     * @return int
     */
    public function getQueueSize()
    {
        return $this->promises->count();
    }
}
