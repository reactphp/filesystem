<?php

namespace React\Filesystem;

use React\Filesystem\Filesystem\FilesystemInterface;

class File {

    use SharedTrait;

    public function __construct($filename, FilesystemInterface $filesystem) {
        $this->filename = $filename;
        $this->filesystem = $filesystem;
    }

    protected function getPath() {
        return $this->filename;
    }

    public function exists() {
        return $this->filesystem->stat($this->filename)->then(function($result) {
            return $result;
        });
    }

    public function size() {
        return $this->filesystem->stat($this->filename)->then(function ($result) {
            return $result['size'];
        });
    }

    public function time() {
        return $this->filesystem->stat($this->filename)->then(function ($result) {
            return [
                'atime' => $result['atime'],
                'ctime' => $result['ctime'],
                'mtime' => $result['mtime'],
            ];
        });
    }

    public function remove() {
        return $this->filesystem->unlink($this->filename);
    }
}
