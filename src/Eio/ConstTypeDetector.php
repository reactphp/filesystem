<?php

namespace React\Filesystem\Eio;

use Exception;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\TypeDetectorInterface;

class ConstTypeDetector implements TypeDetectorInterface
{
    /**
     * @var array
     */
    protected $mapping = [
        EIO_DT_DIR => 'dir',
        EIO_DT_REG => 'file',
        EIO_DT_LNK => 'constructLink',
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
        if (!isset($node['type']) || !isset($this->mapping[$node['type']])) {
            return \React\Promise\reject(new Exception('Unknown node type'));
        }

        return \React\Promise\resolve([
            $this->filesystem,
            $this->mapping[$node['type']],
        ]);
    }
}
