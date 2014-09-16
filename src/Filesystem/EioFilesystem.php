<?php

namespace React\Filesystem\Filesystem;

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Filesystem\EioException;

class EioFilesystem implements FilesystemInterface {

    protected $active = false;
    protected $loop;

    public function __construct(LoopInterface $loop)
    {
        eio_init();
        $this->loop = $loop;
        $this->fd = eio_get_event_stream();
    }

    public function getLoop() {
        return $this->loop;
    }

    public function stat($filename) {
        return $this->callEio('eio_stat', [$filename]);
    }

    public function unlink($filename) {
        return $this->callEio('eio_unlink', [$filename]);
    }

    public function chmod($path, $mode) {
        return $this->callEio('eio_chmod', [$path, $mode]);
    }

    public function chown($path, $uid, $gid) {
        return $this->callEio('eio_chown', [$path, $uid, $gid]);
    }

    public function ls($path, $flags = EIO_READDIR_DIRS_FIRST) {
        return $this->callEio('eio_readdir', [$path, $flags]);
    }

    protected function callEio($function, $args) {
        $this->register();

        $deferred = new Deferred();
        $args[] = EIO_PRI_DEFAULT;
        $args[] = function($void, $result, $req) use ($deferred) {
            if ($result == -1) {
                $deferred->reject(new EioException(eio_get_last_error($req)));
                return;
            }

            $deferred->resolve($result);
        };
        if (!@call_user_func_array($function, $args)) {
            $deferred->reject(new EioException($function . ' unknown error'));
        };
        return $deferred->promise();
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
        $this->unregister();
    }
}
