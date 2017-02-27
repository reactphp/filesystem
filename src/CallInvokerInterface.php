<?php

namespace React\Filesystem;

use React\Promise\PromiseInterface;

interface CallInvokerInterface
{
    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter);

    /**
     * Call the given $function with the given $args,
     * when appropriate for the concrete invoker.
     *
     * @param string $function
     * @param array $args
     * @param int $errorResultCode
     * @return PromiseInterface
     */
    public function invokeCall($function, $args, $errorResultCode = -1);

    /**
     * Return true when no calls are waiting to be called,
     * otherwise return false.
     *
     * @return bool
     */
    public function isEmpty();
}
