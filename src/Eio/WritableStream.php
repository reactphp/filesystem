<?php

namespace React\Filesystem\Eio;

use Evenement\EventEmitter;
use React\Stream\WritableStreamInterface;

class WritableStream extends EventEmitter implements WritableStreamInterface
{
    public function write($data)
    {

    }

    public function end($data = null)
    {
        if (null !== $data) {
            $this->write($data);
        }

        $this->close();
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;
        $this->emit('end', array($this));
        $this->emit('close', array($this));
        $this->removeAllListeners();
    }

    public function isWritable()
    {

    }
}
