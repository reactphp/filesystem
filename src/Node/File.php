<?php

namespace React\Filesystem\Node;

use React\Filesystem\AdapterInterface;
use React\Promise\Deferred;
use React\Stream\BufferedSink;

class File implements FileInterface, GenericOperationInterface
{

    use GenericOperationTrait;

    protected $open = false;
    protected $fileDescriptor;

    public function __construct($filename, AdapterInterface $filesystem)
    {
        $this->filename = $filename;
        $this->filesystem = $filesystem;
    }

    public function getPath()
    {
        return $this->filename;
    }

    public function exists()
    {
        return $this->stat();
    }

    public function size()
    {
        return $this->filesystem->stat($this->filename)->then(function ($result) {
            return $result['size'];
        });
    }

    public function time()
    {
        return $this->filesystem->stat($this->filename)->then(function ($result) {
            return [
                'atime' => $result['atime'],
                'ctime' => $result['ctime'],
                'mtime' => $result['mtime'],
            ];
        });
    }

    public function rename($toFilename)
    {
        return $this->filesystem->rename($this->filename, $toFilename);
    }

    public function create()
    {
        $deferred = new Deferred();

        $this->stat()->then(null, function () use ($deferred) {
            $deferred->reject(new \Exception('File exists'));
        })->then(function () {
            return $this->filesystem->touch($this->filename);
        });

        return $deferred->promise();
    }

    public function open($flags)
    {
        return $this->filesystem->open($this->filename, $flags)->then(function ($stream) {
            $this->open = true;
            return $stream;
        });
    }

    public function close()
    {
        $deferred = new Deferred();

        return $this->filesystem->close($this->fileDescriptor)->then(function () use ($deferred) {
            $this->open = false;

            return $deferred->promise();
        });
    }

    public function getContents()
    {
        return $this->open('r')->then(function($stream) {
            return BufferedSink::createPromise($stream);
        });
    }

    public function remove()
    {
        return $this->filesystem->unlink($this->filename);
    }
}
