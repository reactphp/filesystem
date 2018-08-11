<?php

namespace React\Filesystem;

use React\Promise\Deferred;

class OpenFileLimiter
{
    /**
     * @var int
     */
    const DEFAULT_LIMIT = 512;

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
    public function __construct($limit = self::DEFAULT_LIMIT)
    {
        $this->limit = $limit;
        $this->promises = new \SplQueue();
    }

    /**
     * @return \React\Promise\PromiseInterface
     */
    public function open()
    {
        if ($this->current < $this->limit) {
            $this->current++;
            return \React\Promise\resolve();
        }

        $deferred = new Deferred();
        $this->promises->enqueue($deferred);
        return $deferred->promise();
    }

    public function close()
    {
        if (!$this->promises->isEmpty()) {
            $this->promises->dequeue()->resolve();
        } else {
            $this->current--;
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
