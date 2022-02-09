<?php

namespace React\Filesystem;

use UV;

interface PollInterface
{
    public function activate(): void;

    public function deactivate(): void;
}
