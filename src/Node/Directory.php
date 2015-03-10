<?php

namespace React\Filesystem\Node;

use React\Filesystem\AdapterInterface;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;

class Directory implements DirectoryInterface, GenericOperationInterface
{

    use GenericOperationTrait;

    protected $typeClassMapping = [
        EIO_DT_DIR => '\React\Filesystem\Node\Directory',
        EIO_DT_REG => '\React\Filesystem\Node\File',
    ];

    protected $recursiveInvoker;

    /**
     * @return RecursiveInvoker
     */
    protected function getRecursiveInvoker()
    {
        if ($this->recursiveInvoker instanceof RecursiveInvoker) {
            return $this->recursiveInvoker;
        }

        $this->recursiveInvoker = new RecursiveInvoker($this);
        return $this->recursiveInvoker;
    }

    /**
     * @param $path
     * @param AdapterInterface $filesystem
     * @param RecursiveInvoker $recursiveInvoker
     */
    public function __construct($path, AdapterInterface $filesystem, RecursiveInvoker $recursiveInvoker = null)
    {
        $this->path = $path;
        $this->filesystem = $filesystem;
        $this->recursiveInvoker = $recursiveInvoker;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritDoc}
     */
    public function ls()
    {
        return $this->filesystem->ls($this->path)->then(function ($result) {
            return $this->processLsContents($result);
        });
    }

    /**
     * @todo Move to EioAdapter
     * @param $result
     * @return array
     */
    protected function processLsContents($result)
    {
        $list = [];
        if (isset($result['dents'])) {
            foreach ($result['dents'] as $entry) {
                $path = $this->path . DIRECTORY_SEPARATOR . $entry['name'];
                if (isset($this->typeClassMapping[$entry['type']])) {
                    $list[$entry['name']] = \React\Promise\resolve(new $this->typeClassMapping[$entry['type']]($path, $this->filesystem));
                    continue;
                }

                if ($entry['type'] === EIO_DT_UNKNOWN) {
                    $list[$entry['name']] = $this->filesystem->stat($path)->then(function ($stat) use ($path) {
                        switch (true) {
                            case ($stat['mode'] & 0x4000) == 0x4000:
                                return \React\Promise\resolve(new Directory($path, $this->filesystem));
                                break;
                            case ($stat['mode'] & 0x8000) == 0x8000:
                                return \React\Promise\resolve(new File($path, $this->filesystem));
                                break;
                        }
                    });
                }
            }
        }

        return \React\Promise\all($list);
    }

    /**
     * {@inheritDoc}
     */
    public function size($recursive = false)
    {
        return $this->ls()->then(function ($result) use ($recursive) {
            return $this->processSizeContents($result, $recursive);
        });
    }

    /**
     * @param $nodes
     * @param $recursive
     * @return \React\Promise\Promise
     */
    protected function processSizeContents($nodes, $recursive)
    {
        $numbers = [
            'directories' => 0,
            'files' => 0,
            'size' => 0,
        ];

        $promises = [];
        foreach ($nodes as $node) {
            switch (true) {
                case $node instanceof Directory:
                    $numbers['directories']++;
                    if ($recursive) {
                        $promises[] = $node->size()->then(function ($size) use (&$numbers) {
                            $numbers['directories'] += $size['directories'];
                            $numbers['files'] += $size['files'];
                            $numbers['size'] += $size['size'];
                            return new FulfilledPromise();
                        });
                    }
                    break;
                case $node instanceof File:
                    $numbers['files']++;
                    $promises[] = $node->size()->then(function ($size) use (&$numbers) {
                        $numbers['size'] += $size;
                        return new FulfilledPromise();
                    });
                    break;
            }
        }

        return \React\Promise\all($promises)->then(function () use (&$numbers) {
            return $numbers;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function create()
    {
        return $this->filesystem->mkdir($this->path);
    }

    /**
     * {@inheritDoc}
     */
    public function remove()
    {
        return $this->filesystem->rmdir($this->path);
    }

    /**
     * {@inheritDoc}
     */
    public function createRecursive()
    {
        $parentPath = explode(DIRECTORY_SEPARATOR, $this->path);
        array_pop($parentPath);
        $parentPath = implode(DIRECTORY_SEPARATOR, $parentPath);

        $parentDirectory = new Directory($parentPath, $this->filesystem);
        $parentDirectory->stat()->then(null, function () use ($parentDirectory) {
            return $parentDirectory->createRecursive();
        })->then(function () {
            return $this->create();
        })->then(function () {
            return new FulfilledPromise();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function chmodRecursive($mode)
    {
        return $this->getRecursiveInvoker()->execute('chmod', [$mode]);
    }

    /**
     * {@inheritDoc}
     */
    public function chownRecursive($uid = -1, $gid = -1)
    {
        return $this->getRecursiveInvoker()->execute('chown', [$uid, $gid]);
    }

    /**
     * {@inheritDoc}
     */
    public function removeRecursive()
    {
        return $this->getRecursiveInvoker()->execute('remove', []);
    }

    /**
     * {@inheritDoc}
     */
    public function sizeRecursive()
    {
        return $this->size(true);
    }

    /**
     * {@inheritDoc}
     */
    public function lsRecursive(\SplObjectStorage $list = null)
    {
        if ($list === null) {
            $list = new \SplObjectStorage();
        }
        return $this->ls()->then(function ($nodes) use ($list) {
            return $this->processLsRecursiveContents($nodes, $list);
        });
    }

    /**
     * @param $nodes
     * @param $list
     * @return \React\Promise\Promise
     */
    protected function processLsRecursiveContents($nodes, $list)
    {
        $promises = [];
        foreach ($nodes as $node) {
            if ($node instanceof Directory || $node instanceof File) {
                $list->attach($node);
            }
            if ($node instanceof Directory) {
                $promises[] = $node->lsRecursive($list);
            }
        }

        return \React\Promise\all($promises)->then(function () use ($list) {
            return $list;
        });
    }
}
