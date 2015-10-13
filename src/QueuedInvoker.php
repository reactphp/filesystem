<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

class QueuedInvoker implements CallInvokerInterface
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
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->loop = $adapter->getLoop();
        $this->adapter = $adapter;
        $this->callQueue = new \SplQueue();
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
        $this->loop->futureTick(function () {
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
     * @param callable $func
     * @return callable
     */
    protected function filesystemResultHandler(callable $func)
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
