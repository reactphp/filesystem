<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

class ThrottledQueuedInvoker implements CallInvokerInterface
{
    /**
     * @var float
     */
    const DEFAULT_INTERVAL = 0.1;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var \SplQueue
     */
    protected $callQueue;

    /**
     * @var bool
     */
    protected $callQueueActive = false;

    /**
     * @var float
     */
    protected $interval = self::DEFAULT_INTERVAL;

    /**
     * @param AdapterInterface $adapter
     * @param float $interval
     */
    public function __construct(AdapterInterface $adapter, $interval = self::DEFAULT_INTERVAL)
    {
        $this->loop = $adapter->getLoop();
        $this->adapter = $adapter;
        $this->callQueue = new \SplQueue();
        $this->interval = $interval;
    }
    /**
     * @param float $interval
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;
    }

    /**
     * @return float
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * @param string $function
     * @param array $args
     * @param int $errorResultCode
     * @return \React\Promise\ExtendedPromiseInterface
     */
    public function invokeCall($function, $args, $errorResultCode = -1)
    {
        $this->callQueueActive = true;
        $deferred = new Deferred();

        $this->callQueue->enqueue(new QueuedCall($deferred, $function, $args, $errorResultCode));

        if (!$this->callQueue->isEmpty()) {
            $this->processQueue();
        }

        return $deferred->promise()->then(function ($data) {
            return $this->
                adapter->
                callFilesystem($data['function'], $data['args'], $data['errorResultCode'])->
                then($this->filesystemResultHandler('React\Promise\resolve'), $this->filesystemResultHandler('React\Promise\reject'));
        });
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->callQueue->isEmpty();
    }

    protected function processQueue()
    {
        $this->loop->addTimer($this->interval, function () {
            if ($this->callQueue->isEmpty()) {
                return;
            }

            $message = $this->callQueue->dequeue();
            $data = [
                'function' => $message->getFunction(),
                'args' => $message->getArgs(),
                'errorResultCode' => $message->getErrorResultCode(),
            ];
            $message->getDeferred()->resolve($data);
        });
    }

    /**
     * @param $func
     *
     * @return callable
     */
    protected function filesystemResultHandler($func)
    {
        return function ($mixed) use ($func) {
            if ($this->callQueue->count() == 0) {
                $this->callQueueActive = false;
            } else {
                $this->processQueue();
            }
            return $func($mixed);
        };
    }
}
