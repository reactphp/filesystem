<?php

namespace React\Filesystem;

interface DirectoryInterface {
    public function create();
    public function remove();
    public function listContents();
}
