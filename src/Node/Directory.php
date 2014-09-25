<?php

namespace React\Filesystem\Node;

use React\Filesystem\FilesystemInterface;
use React\Promise\Deferred;

class Directory implements DirectoryInterface, GenericOperationInterface
{

    use GenericOperationTrait;

    protected $typeClassMapping = [
        EIO_DT_DIR => '\React\Filesystem\Node\Directory',
        EIO_DT_REG => '\React\Filesystem\Node\File',
    ];

    protected $recursiveInvoker;

    protected function getRecursiveInvoker()
    {
        if ($this->recursiveInvoker instanceof RecursiveInvoker) {
            return $this->recursiveInvoker;
        }

        $this->recursiveInvoker = new RecursiveInvoker($this);
        return $this->recursiveInvoker;
    }

    public function __construct($path, FilesystemInterface $filesystem)
    {
        $this->path = $path;
        $this->filesystem = $filesystem;
    }

    protected function getPath()
    {
        return $this->path;
    }

    public function ls()
    {
        $deferred = new Deferred();

        $this->filesystem->ls($this->path)->then(function ($result) use ($deferred) {
            $this->filesystem->getLoop()->futureTick(function () use ($result, $deferred) {
                $this->processLsContents($result, $deferred);
            });
        }, function ($error) use ($deferred) {
            $deferred->reject($error);
        });

        return $deferred->promise();
    }

    protected function processLsContents($result, $deferred)
    {
        $list = [];
        foreach ($result['dents'] as $entry) {
            if (isset($this->typeClassMapping[$entry['type']])) {
                $path = $this->path . DIRECTORY_SEPARATOR . $entry['name'];
                $list[$entry['name']] = new $this->typeClassMapping[$entry['type']]($path, $this->filesystem);
            }
        }
        $deferred->resolve($list);
    }

    public function create()
    {
        return $this->filesystem->mkdir($this->path);
    }

    public function remove()
    {
        return $this->filesystem->rmdir($this->path);
    }

    public function createRecursive()
    {
        $deferred = new Deferred();

        $parentPath = explode(DIRECTORY_SEPARATOR, $this->path);
        array_pop($parentPath);
        $parentPath = implode(DIRECTORY_SEPARATOR, $parentPath);

        $parentDirectory = new Directory($parentPath, $this->filesystem);
        $parentDirectory->stat()->then(null, function () use ($parentDirectory, $deferred) {
            return $parentDirectory->createRecursive();
        })->then(function () use ($deferred) {
            return $this->create();
        })->then(function () use ($deferred) {
            $deferred->resolve();
        });

        return $deferred->promise();
    }

    public function chmodRecursive($mode)
    {
        return $this->getRecursiveInvoker()->execute('chmod', [$mode]);
    }

    public function chownRecursive($uid = -1, $gid = -1)
    {
        return $this->getRecursiveInvoker()->execute('chown', [$uid, $gid]);
    }

    public function removeRecursive()
    {
        return $this->getRecursiveInvoker()->execute('remove', []);
    }
}
