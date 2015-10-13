<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

class PooledInvoker implements CallInvokerInterface
{
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
     * @var int
     */
    protected $maxSimultaneousOperations = 133;

    /**
     * @var int
     */
    protected $runningOperations = 0;

    /**
     * @param AdapterInterface $adapter
     * @param int $maxSimultaneousOperations
     */
    public function __construct(AdapterInterface $adapter, $maxSimultaneousOperations = 133)
    {
        $this->loop = $adapter->getLoop();
        $this->adapter = $adapter;
        $this->callQueue = new \SplQueue();
        $this->maxSimultaneousOperations = $maxSimultaneousOperations;
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

        if (!$this->callQueue->isEmpty() && $this->runningOperations < $this->maxSimultaneousOperations) {
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
        $this->loop->futureTick(function () {
            if ($this->callQueue->isEmpty()) {
                return;
            }

            $this->runningOperations++;

            $message = $this->callQueue->dequeue();
            $data = [
                'function' => $message->getFunction(),
                'args' => $message->getArgs(),
                'errorResultCode' => $message->getErrorResultCode(),
            ];

            $message->getDeferred()->resolve($data);
        });
    }

    protected function filesystemResultHandler($func)
    {
        return function ($mixed) use ($func) {
            if ($this->callQueue->count() == 0) {
                $this->callQueueActive = false;
            } else {
                $this->processQueue();
            }

            $this->runningOperations--;

            return $func($mixed);
        };
    }
}
