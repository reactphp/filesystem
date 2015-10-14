<?php

namespace React\Filesystem;

/**
 * @param AdapterInterface $adapter
 * @param array $options
 * @param string $fallback
 * @return CallInvokerInterface
 */
function getInvoker(AdapterInterface $adapter, array $options, $key, $fallback)
{
    if (isset($options[$key]) && $options[$key] instanceof CallInvokerInterface) {
        return $options[$key];
    }

    return new $fallback($adapter);
}

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
