#!/bin/bash

mkdir `php -r "echo sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'react-filesystem-tests' . DIRECTORY_SEPARATOR;"`
chmod 0777 -Rfv `php -r "echo sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'react-filesystem-tests' . DIRECTORY_SEPARATOR;"`

if [[ "$TRAVIS_PHP_VERSION" != "hhvm" ]]; then
    # install 'eio' PHP extension (does not support nightly)
    if [[ "$TRAVIS_PHP_VERSION" != "nightly" ]]; then
        yes "" | pecl install eio
    fi
fi

#set -e
#set -o pipefail

if [[ "$TRAVIS_PHP_VERSION" != "hhvm" &&
      "$TRAVIS_PHP_VERSION" != "hhvm-nightly" ]]; then
    # travis does not seem to have libuv1 ?
    git clone https://github.com/libuv/libuv && cd libuv && git checkout tags/v1.28.0 && sh autogen.sh && ./configure && make && sudo make install && cd ..

    # install ext-uv
    yes | pecl install uv-beta

    # install "libevent" (used by 'event' and 'libevent' PHP extensions)
#    sudo apt-get install -y libevent-dev

    # install 'event' PHP extension
#    echo "yes" | pecl install event

    # install 'libevent' PHP extension (does not support php 7)
#    if [[ "$TRAVIS_PHP_VERSION" != "7.0" ]]; then
#        curl http://pecl.php.net/get/libevent-0.1.0.tgz | tar -xz
#        pushd libevent-0.1.0
#        phpize
#       ./configure
#       make
#       make install
#       popd
#       echo "extension=libevent.so" >> "$(php -r 'echo php_ini_loaded_file();')"
#    fi

    # install 'libev' PHP extension (does not support php 7)
#    if [[ "$TRAVIS_PHP_VERSION" != "7.0" ]]; then
#        git clone --recursive https://github.com/m4rw3r/php-libev
#        pushd php-libev
#        phpize
#        ./configure --with-libev
#        make
#        make install
#        popd
#        echo "extension=libev.so" >> "$(php -r 'echo php_ini_loaded_file();')"
#    fi

fi
