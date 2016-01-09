<?php

namespace React\Filesystem\Pthreads;

class Yarn extends \Thread
{
    /**
     * @var string
     */
    protected $function;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var mixed
     */
    protected $result;

    /**
     * Yarn constructor.
     * @param string $function
     * @param array $args
     */
    public function __construct($function, array $args)
    {
        $this->function = $function;
        $this->args = $args;
        $this->start();
    }

    public function run()
    {
        $this->synchronized(function () {
            $this->result = call_user_func_array($this->function, $this->args);
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
