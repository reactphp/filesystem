<?php

namespace React\Filesystem\Node;

use React\Filesystem\FilesystemInterface;
use React\Promise\Deferred;

class File implements FileInterface, GenericOperationInterface
{

    use GenericOperationTrait;

    protected $open = false;
    protected $fileDescriptor;

    public function __construct($filename, FilesystemInterface $filesystem)
    {
        $this->filename = $filename;
        $this->filesystem = $filesystem;
    }

    protected function getPath()
    {
        return $this->filename;
    }

    public function exists()
    {
        return $this->filesystem->stat($this->filename)->then(function ($result) {
            return $result;
        });
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

    public function move($toFilename)
    {
        return $this->filesystem->move($this->filename, $toFilename);
    }

    public function create()
    {
        $deferred = new Deferred();

        $this->stat()->then(null, function () use ($deferred) {
            $deferred->reject(new EioException('File exists'));
        })->then(function () {
            return $this->filesystem->touch($this->filename);
        });

        return $deferred->promise();
    }

    public function open($flags)
    {
        $deferred = new Deferred();

        return $this->filesystem->open($this->filename, $flags)->then(function ($fd) use ($deferred) {
            $this->open = true;
            $this->fileDescriptor = $fd;

            return $deferred->promise();
        });
    }

    public function close()
    {
        $deferred = new Deferred();

        return $this->filesystem->close($this->fileDescriptor)->then(function () use ($deferred) {
            $this->open = false;
            $this->fileDescriptor = null;

            return $deferred->promise();
        });
    }

    public function remove()
    {
        return $this->filesystem->unlink($this->filename);
    }
}
