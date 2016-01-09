<?php

namespace React\Filesystem\Pthreads;

class Call
{
    /**
     * @var callable
     */
    protected $function;

    /**
     * @var array
     */
    protected $args;

    /**
     * Call constructor.
     * @param callable $function
     * @param array $args
     */
    public function __construct(callable $function, array $args)
    {
        $this->function = $function;
        $this->args = $args;
    }

    /**
     * @return mixed
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * @return mixed
     */
    public function getArgs()
    {
        return $this->args;
    }
}
