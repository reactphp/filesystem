<?php

namespace React\Filesystem\Stream;

use Exception;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

trait ReadableStreamTrait
{
    protected $size;
    protected $readCursor;
    protected $chunkSize = 8192;
    protected $pause = true;
    protected $isReading = false;
    private $sizeLookupPromise;

    public function resume()
    {
        if (!$this->pause || $this->isReading) {
            return;
        }

        $this->pause = false;

        if ($this->size === null && $this->sizeLookupPromise === null) {
            $this->sizeLookupPromise = $this->getFilesystem()->stat($this->getPath())->then(function ($info) {
                if ($this->size !== null) {
                    throw new Exception('File was already stat-ed');
                }

                $this->size = $info['size'];
                $this->readCursor = 0;
            });
        }

        $this->sizeLookupPromise->then(function () {
            $this->readChunk();
        });
    }

    public function pause()
    {
        $this->pause = true;
    }

    public function pipe(WritableStreamInterface $dest, array $options = [])
    {
        if ($this === $dest) {
            throw new \Exception('Can\'t pipe stream into itself!');
        }

        Util::pipe($this, $dest, $options);

        return $dest;
    }

    public function isReadable()
    {
        return !$this->isClosed();
    }

    protected function readChunk()
    {
        if ($this->pause || $this->isReading) {
            return;
        }

        $this->performRead($this->calculateChunkSize());
    }

    protected function calculateChunkSize()
    {
        if ($this->readCursor + $this->chunkSize > $this->size) {
            return $this->size - $this->readCursor;
        }

        return $this->chunkSize;
    }

    protected function performRead($chunkSize)
    {
        $this->isReading = true;
        $this->getFilesystem()->read($this->getFileDescriptor(), $chunkSize, $this->readCursor)->then(function ($data) use ($chunkSize) {
            $this->isReading = false;
            if ($this->pause) {
                return;
            }

            // If chunk size can be set make sure to copy it before running this operation so
            // that used can't change it mid operation and cause funkyness.
            $this->readCursor += $chunkSize;
            $this->emit('data', [
                $data,
                $this,
            ]);

            if ($this->readCursor < $this->size) {
                $this->readChunk();
            } else {
                $this->emit('end', [$this]);
                $this->close();
            }
        });
    }

    abstract function close();
}
