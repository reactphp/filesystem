<?php

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Defining these constants so they don't cause errors during tests when EIO isn't installed
 */
if (!extension_loaded('eio')) {
    define('EIO_DT_REG', 1);
    define('EIO_DT_DIR', 1);
    define('EIO_S_IROTH', 1);
    define('EIO_S_IXOTH', 1);
    define('EIO_S_IWOTH', 1);
    define('EIO_S_IRGRP', 1);
    define('EIO_S_IXGRP', 1);
    define('EIO_S_IWGRP', 1);
    define('EIO_S_IRUSR', 1);
    define('EIO_S_IXUSR', 1);
    define('EIO_S_IWUSR', 1);
}
