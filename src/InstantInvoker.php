<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;

class InstantInvoker implements CallInvokerInterface
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
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->loop = $adapter->getLoop();
        $this->adapter = $adapter;
    }

    /**
     * @param string $function
     * @param array $args
     * @param int $errorResultCode
     * @return \React\Promise\ExtendedPromiseInterface
     */
    public function invokeCall($function, $args, $errorResultCode = -1)
    {
        return $this->
            adapter->
            callFilesystem($function, $args, $errorResultCode);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return true;
    }
}
