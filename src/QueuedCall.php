<?php

namespace React\Filesystem;

use React\Promise\Deferred;

class QueuedCall
{
    /**
     * @var Deferred
     */
    protected $deferred;

    /**
     * @var string
     */
    protected $function;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var int
     */
    protected $errorResultCode;

    /**
     * @param Deferred $deferred
     * @param string $function
     * @param array $args
     * @param int $errorResultCode
     */
    public function __construct(Deferred $deferred, $function, array $args, $errorResultCode)
    {
        $this->deferred = $deferred;
        $this->function = $function;
        $this->args = $args;
        $this->errorResultCode = $errorResultCode;
    }

    /**
     * @return Deferred
     */
    public function getDeferred()
    {
        return $this->deferred;
    }

    /**
     * @return string
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @return int
     */
    public function getErrorResultCode()
    {
        return $this->errorResultCode;
    }
}
