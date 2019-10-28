<?php

namespace React\Filesystem;

use Exception;

/**
 * @param array $options
 * @return int
 */
function getOpenFileLimit(array $options)
{
    if (isset($options['open_file_limit'])) {
        return (int)$options['open_file_limit'];
    }

    return OpenFileLimiter::DEFAULT_LIMIT;
}

/**
 * @param array $typeDetectors
 * @param array $node
 * @return \React\Promise\PromiseInterface
 */
function detectType(array $typeDetectors, array $node)
{
    $promiseChain = \React\Promise\reject(new Exception('Unknown type'));
    foreach ($typeDetectors as $detector) {
        $promiseChain = $promiseChain->otherwise(function () use ($node, $detector) {
            return $detector->detect($node);
        });
    }

    return $promiseChain->then(function ($callable) use ($node) {
        return \React\Promise\resolve($callable($node['path']));
    });
}
