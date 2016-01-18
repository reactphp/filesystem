<?php

namespace React\Filesystem\Pthreads;

use React\Promise\Deferred;

class Yarn extends \Thread
{
    /**
     * @var string
     */
    protected $call;

    /**
     * @var mixed
     */
    protected $result;

    /**
     * @var Deferred
     */
    protected $deferred;

    /**
     * Yarn constructor.
     * @param Call $call
     */
    public function __construct(Call $call)
    {
        $this->call = $call;
        $this->deferred = new Deferred();
    }

    public function call()
    {
        $this->synchronized(function () {
            if (!$this->result) {
                $this->wait();
            }
            $this->deferred->resolve($this->result);
        });
        $this->start();
        return $this->deferred->promise();
    }

    public function run()
    {
        $this->synchronized(function () {
            $this->result = call_user_func_array($this->call->getFunction(), $this->call->getArgs());
            $this->notify();
        });
    }
}
