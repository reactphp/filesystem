<?php
namespace React\Filesystem;

use React\EventLoop\LoopInterface;
use React\Filesystem\ChildProcess\PooledInvoker as ChildProcessInvoker;
use React\Filesystem\ChildProcess\StreamFactory;

class ChildProcessAdapter implements AdapterInterface
{
    protected $loop;
    protected $invoker;
    protected $permissionResolver;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->invoker = new ChildProcessInvoker($this, 64);
    }

    /**
     * {@inheritDoc}
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * {@inheritDoc}
     */
    public function setInvoker(CallInvokerInterface $invoker)
    {
        $this->invoker = $invoker;
    }

    /**
     * {@inheritDoc}
     */
    public function stat($filename)
    {
        return $this->invoker->invokeCall('stat', [$filename]);
    }

    /**
     * {@inheritDoc}
     */
    public function unlink($filename)
    {
        return $this->invoker->invokeCall('unlink', [$filename]);
    }

    /**
     * {@inheritDoc}
     */
    public function rename($fromFilename, $toFilename)
    {
        return $this->invoker->invokeCall('rename', [$fromFilename, $toFilename]);
    }

    /**
     * {@inheritDoc}
     */
    public function chmod($path, $mode)
    {
        return $this->invoker->invokeCall('chmod', [$path, $mode]);
    }

    /**
     * {@inheritDoc}
     */
    public function chown($path, $uid, $gid)
    {
        return $this->invoker->invokeCall('chown', [$path, $uid, $gid]);
    }

    /**
     * {@inheritDoc}
     */
    public function ls($path, $flags = null)
    {
        return $this->invoker->invokeCall('readdir', [$path, $flags]);
    }

    /**
     * {@inheritDoc}
     */
    public function mkdir($path, $mode = self::CREATION_MODE)
    {
        return $this->invoker->invokeCall('mkdir', [
            $path,
            $this->permissionResolver->resolve($mode),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function rmdir($path)
    {
        return $this->invoker->invokeCall('rmdir', [$path]);
    }

    /**
     * {@inheritDoc}
     */
    public function open($path, $flags, $mode = null)
    {
        return $this->invoker->invokeCall('open', [
            $path,
            $flags,
            $mode
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function close($fd)
    {
        return $this->invoker->invokeCall('close', [$fd]);
    }

    /**
     * {@inheritDoc}
     */
    public function touch($path, $mode = self::CREATION_MODE)
    {
        return $this->invoker->invokeCall('touch', [$path, $mode]);
    }

    /**
     * {@inheritDoc}
     */
    public function read($fileDescriptor, $length, $offset)
    {
        return $this->invoker->invokeCall('read', [
            $fileDescriptor,
            $length,
            $offset
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function write($fileDescriptor, $data, $length, $offset)
    {
        return $this->invoker->invokeCall('write', [
            $fileDescriptor,
            $data,
            $length,
            $offset
        ]);
    }

    /**
     * @param string $function
     * @param array $args
     * @param int $errorResultCode
     * @return \React\Promise\Promise
     */
    public function callFilesystem($function, $args, $errorResultCode = -1)
    {
        // When is this used?
    }
}
