<?php

namespace React\Filesystem;

interface TypeDetectorInterface
{
    /**
     * @param FilesystemInterface $filesystem
     */
    public function __construct(FilesystemInterface $filesystem);

    /**
     * @param array $node
     * @return React\Promise\PromiseInterface
     */
    public function detect(array $node);
}
