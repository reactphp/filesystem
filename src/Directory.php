<?php

namespace React\Filesystem;

use React\Filesystem\Filesystem\FilesystemInterface;
use React\Promise\Deferred;

class Directory {

    use SharedTrait;

    protected $typeClassMapping = [
        EIO_DT_DIR => '\React\Filesystem\Directory',
        EIO_DT_REG => '\React\Filesystem\File',
    ];

    public function __construct($path, FilesystemInterface $filesystem) {
        $this->path = $path;
        $this->filesystem = $filesystem;
    }

    protected function getPath() {
        return $this->path;
    }

    public function ls() {
        $deferred = new Deferred();

        $this->filesystem->ls($this->path)->then(function($result) use ($deferred) {
            $this->filesystem->getLoop()->futureTick(function() use ($result, $deferred) {
                $this->processLsContents($result, $deferred);
            });
        }, function($error) use ($deferred) {
            $deferred->reject($error);
        });

        return $deferred->promise();
    }

    protected function processLsContents($result, $deferred) {
        $list = [];
        foreach ($result['dents'] as $entry) {
            $path = $this->path . DIRECTORY_SEPARATOR . $entry['name'];
            $list[$entry['name']] = new $this->typeClassMapping[$entry['type']]($path, $this->filesystem);
        }
        $deferred->resolve($list);
    }
}
