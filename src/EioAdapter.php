<?php

namespace React\Filesystem;

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Filesystem\Eio;

class EioAdapter implements AdapterInterface
{

    const CREATION_MODE = 'rw-rw-rw-';

    protected $active = false;
    protected $loop;
    protected $openFlagResolver;
    protected $permissionFlagResolver;

    public function __construct(LoopInterface $loop)
    {
        eio_init();
        $this->loop = $loop;
        $this->fd = eio_get_event_stream();
        $this->openFlagResolver = new Eio\OpenFlagResolver();
        $this->permissionFlagResolver = new Eio\PermissionFlagResolver();
    }

    /**
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * {@inheritDoc}
     */
    public function stat($filename)
    {
        return $this->callEio('eio_stat', [$filename]);
    }

    /**
     * {@inheritDoc}
     */
    public function unlink($filename)
    {
        return $this->callEio('eio_unlink', [$filename]);
    }

    /**
     * {@inheritDoc}
     */
    public function rename($fromFilename, $toFilename)
    {
        return $this->callEio('eio_rename', [$fromFilename, $toFilename]);
    }

    /**
     * {@inheritDoc}
     */
    public function chmod($path, $mode)
    {
        return $this->callEio('eio_chmod', [$path, $mode]);
    }

    /**
     * {@inheritDoc}
     */
    public function chown($path, $uid, $gid)
    {
        return $this->callEio('eio_chown', [$path, $uid, $gid]);
    }

    /**
     * {@inheritDoc}
     */
    public function ls($path, $flags = EIO_READDIR_DIRS_FIRST)
    {
        return $this->callEio('eio_readdir', [$path, $flags], false);
    }

    /**
     * {@inheritDoc}
     */
    public function mkdir($path, $mode = self::CREATION_MODE)
    {
        return $this->callEio('eio_mkdir', [
            $path,
            $this->permissionFlagResolver->resolve($mode),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function rmdir($path)
    {
        return $this->callEio('eio_rmdir', [$path]);
    }

    /**
     * {@inheritDoc}
     */
    public function open($path, $flags, $mode = self::CREATION_MODE)
    {
        $flags = $this->openFlagResolver->resolve($flags);
        return $this->callEio('eio_open', [
            $path,
            $flags,
            $this->permissionFlagResolver->resolve($mode),
        ])->then(function ($fileDescriptor) use ($path, $flags) {
            return Eio\StreamFactory::create($path, $fileDescriptor, $flags, $this);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function close($fd)
    {
        return $this->callEio('eio_close', [$fd]);
    }

    /**
     * {@inheritDoc}
     */
    public function touch($path, $mode = self::CREATION_MODE)
    {
        return $this->callEio('eio_open', [
            $path,
            EIO_O_CREAT,
            $this->permissionFlagResolver->resolve($mode),
        ])->then(function ($fd) use ($path) {
            return $this->close($fd);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function read($fileDescriptor, $length, $offset)
    {
        return $this->callEio('eio_read', [
            $fileDescriptor,
            $length,
            $offset,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function write($fileDescriptor, $data, $length, $offset)
    {
        return $this->callEio('eio_write', [
            $fileDescriptor,
            $data,
            $length,
            $offset,
        ]);
    }

    /**
     * @param string $function
     * @param array $args
     * @param int $errorResultCode
     * @return \React\Promise\Promise
     */
    protected function callEio($function, $args, $errorResultCode = -1)
    {
        $deferred = new Deferred();

        // Run this in a future tick to make sure all EIO calls are run within the loop
        $this->loop->futureTick(function () use ($function, $args, $errorResultCode, $deferred) {
            $this->executeDelayedCall($function, $args, $errorResultCode, $deferred);
        });

        return $deferred->promise();
    }

    protected function executeDelayedCall($function, $args, $errorResultCode, $deferred)
    {
        $this->register();
        $args[] = EIO_PRI_DEFAULT;
        $args[] = function ($data, $result, $req) use ($deferred, $errorResultCode, $function, $args) {
            if ($result == $errorResultCode) {
                $deferred->reject(new \UnexpectedValueException(eio_get_last_error($req)));
                return;
            }

            $deferred->resolve($result);
        };

        if (!@call_user_func_array($function, $args)) {
            $deferred->reject(new \RuntimeException($function . ' unknown error: ' . var_export($args, true)));
        };
    }

    protected function register()
    {
        if ($this->active) {
            return;
        }

        $this->active = true;
        $this->loop->addReadStream($this->fd, [$this, 'handleEvent']);
    }

    protected function unregister()
    {
        if (!$this->active) {
            return;
        }

        $this->active = false;
        $this->loop->removeReadStream($this->fd, [$this, 'handleEvent']);
    }

    public function handleEvent()
    {
        if (!eio_npending()) {
            return;
        }
        while (eio_npending()) {
            eio_poll();
        }

        if (eio_nreqs() == 0 && eio_npending() == 0 && eio_nready() == 0) {
            $this->unregister();
        }
    }
}
