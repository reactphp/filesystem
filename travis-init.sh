#!/bin/bash

if [[ "$TRAVIS_PHP_VERSION" != "hhvm" ]]; then
    # install 'eio' PHP extension (does not support php 7)
    if [[ "$TRAVIS_PHP_VERSION" != "7.0" ]]; then
        yes "" | pecl install eio
        echo "extension=eio.so" >> "$(php -r 'echo php_ini_loaded_file();')"
    fi

fi

composer self-update
composer install -n
