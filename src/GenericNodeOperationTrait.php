<?php

namespace React\Filesystem;

trait GenericNodeOperationTrait {

    public function getFilesystem() {
        return $this->filesystem;
    }

    public function stat() {
        return $this->filesystem->stat($this->getPath());
    }

    public function chmod($mode) {
        return $this->filesystem->chmod($this->getPath(), $mode);
    }

    public function chown($uid = -1, $gid = -1) {
        return $this->filesystem->chown($this->getPath(), $uid, $gid);
    }
}