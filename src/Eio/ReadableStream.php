<?php

namespace React\Filesystem\Eio;

use Evenement\EventEmitter;
use React\Filesystem\EioAdapter;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

class ReadableStream extends EventEmitter implements ReadableStreamInterface
{
    protected $path;
    protected $size;
    protected $filesystem;
    protected $fileDescriptor;
    protected $cursor;
    protected $chunkSize = 8192;

    public function __construct($path, $fileDescriptor, EioAdapter $filesystem)
    {
        $this->path = $path;
        $this->filesystem = $filesystem;
        $this->fileDescriptor = $fileDescriptor;

        $this->resume();
    }

    public function resume()
    {
        if ($this->size === null) {
            $this->filesystem->stat($this->path)->then(function($info) {
                $this->size = $info['size'];
                $this->cursor = 0;

                $this->readChunk();
            });
            return;
        }

        $this->readChunk();
    }

    public function pause()
    {

    }

    public function pipe(WritableStreamInterface $dest, array $options = [])
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }

    public function close()
    {
        $this->filesystem->close($this->fileDescriptor)->then(function() {
            $this->emit('close', [
                $this,
            ]);
        });
    }

    public function isReadable()
    {

    }

    protected function readChunk()
    {
        $this->filesystem->read($this->fileDescriptor, $this->chunkSize, $this->cursor)->then(function($data) {
            $this->cursor += $this->chunkSize; // If chunk size can be set make sure to copy it before running this operation so that used can't change it mid operation and cause funkyness
            $this->emit('data', [
                $data,
                $this,
            ]);

            if ($this->cursor < $this->size) {
                $this->readChunk();
            } else {
                $this->emit('end', [
                    $this,
                ]);
            }
        });
    }
}
