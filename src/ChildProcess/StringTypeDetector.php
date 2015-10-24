<?php

namespace React\Filesystem\ChildProcess;

use React\Filesystem\FilesystemInterface;
use React\Filesystem\TypeDetectorInterface;
use React\Promise\RejectedPromise;

class StringTypeDetector implements TypeDetectorInterface
{
    /**
     * @var array
     */
    protected $mapping = [
        'dir' => 'dir',
        'file' => 'file',
    ];

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @param FilesystemInterface $filesystem
     */
    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param array $node
     * @return \React\Promise\PromiseInterface
     */
    public function detect(array $node)
    {
        if (isset($this->mapping[$node['type']])) {
            return \React\Promise\resolve([
                $this->filesystem,
                $this->mapping[$node['type']],
            ]);
        }

        return new RejectedPromise();
    }
}
