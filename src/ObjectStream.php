<?php

namespace React\Filesystem;

use Evenement\EventEmitter;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

class ObjectStream extends EventEmitter implements ReadableStreamInterface, WritableStreamInterface
{
    protected $closed = false;

    public function isReadable()
    {
        return !$this->closed;
    }

    public function pause()
    {
    }

    public function resume()
    {
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }
    public function write($data)
    {
        $this->emit('data', array($data, $this));
    }

    public function end($data = null)
    {
        if (null !== $data) {
            $this->write($data);
        }

        $this->close();
    }

    public function isWritable()
    {
        return !$this->closed;
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
}
