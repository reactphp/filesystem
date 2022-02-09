<?php

use React\Filesystem\Factory;
use React\Filesystem\Node\NodeInterface;

require 'vendor/autoload.php';

Factory::create()->detect(__FILE__ . time() . time() . time() . time() . time() . time())->then(static function (NodeInterface $node): void {
    echo get_class($node);
})->done();
