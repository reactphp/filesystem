<?php

namespace React\Filesystem\Pthreads;

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
     * Yarn constructor.
     * @param Call $call
     */
    public function __construct(Call $call)
    {
        $this->call = $call;
        $this->start();
    }

    public function run()
    {
        $this->synchronized(function () {
            $this->result = call_user_func_array($this->call->getFunction(), $this->call->getArgs());
            $this->notify();
        });
    }

    /**
     * @param callable $callback
     * @return mixed
     */
    public function then(callable $callback)
    {
        return $this->synchronized(function () use ($callback) {
            if (!$this->result) {
                $this->wait();
            }

            $callback($this->result);
        });
    }
}
