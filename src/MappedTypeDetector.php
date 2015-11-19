<?php

namespace React\Filesystem;

use React\Promise\RejectedPromise;

class MappedTypeDetector implements TypeDetectorInterface
{
    /**
     * @var array
     */
    protected static $defaultMapping = [
        'dir' => 'dir',
        'file' => 'file',
        'link' => 'constructLink',
    ];

    /**
     * @var array
     */
    protected $mapping = [];

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    public static function createDefault(FilesystemInterface $filesystem)
    {
        return new static($filesystem, static::$defaultMapping);
    }

    /**
     * @param FilesystemInterface $filesystem
     * @param array $options
     */
    public function __construct(FilesystemInterface $filesystem, $options = [])
    {
        $this->filesystem = $filesystem;

        if (isset($options['mapping']) && is_array($options['mapping']) && count($options['mapping']) > 0) {
            $this->mapping = $options['mapping'];
        }
    }

    /**
     * @param array $node
     * @return \React\Promise\PromiseInterface
     */
    public function detect(array $node)
    {
        if (!isset($node['type']) || !isset($this->mapping[$node['type']])) {
            return new RejectedPromise();
        }

        return \React\Promise\resolve([
            $this->filesystem,
            $this->mapping[$node['type']],
        ]);
    }
}
