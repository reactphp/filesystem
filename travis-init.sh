#!/bin/bash
set -e
set -o pipefail

if [[ "$TRAVIS_PHP_VERSION" != "hhvm" ]]; then
    # install 'eio' PHP extension (does not support php 7)
    if [[ "$TRAVIS_PHP_VERSION" != "7.0" ]]; then
        yes "" | pecl install eio
        echo "extension=\"eio.so\"" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
    fi

fi

composer self-update
composer install -n
