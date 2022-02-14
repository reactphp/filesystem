# Filesystem Component

[![CI status](https://github.com/reactphp/filesystem/workflows/CI/badge.svg)](https://github.com/reactphp/filesystem/actions)

[ReactPHP](https://reactphp.org/)'s filesystem component that enables non-blocking filesystem operations.

> **Development version:** This branch contains the code for the upcoming 0.2
> release which will be the way forward for this package.
>
> See [installation instructions](#install) for more details.

**Table of Contents**

* [Quickstart example](#quickstart-example)
* [Usage](#usage)
  * [Factory](#factory)
    * [create()](#create)
  * [Filesystem implementations](#filesystem-implementations)
    * [ChildProcess](#childprocess)
    * [Uv](#uv)
  * [AdapterInterface](#adapterinterface)
    * [detect()](#detect)
    * [directory()](#directory)
    * [file()](#file)
  * [NodeInterface](#nodeinterface)
    * [path()](#path)
    * [name()](#name)
    * [stat()](#stat)
  * [DirectoryInterface](#directoryinterface)
    * [ls](#ls)
  * [FileInterface](#fileinterface)
    * [getContents()](#getcontents)
    * [putContents()](#putcontents)
  * [NotExistInterface](#notexistinterface)
    * [createDirectory()](#createdirectory)
    * [createFile()](#createfile)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## Quickstart example

Here is a program that lists everything in the current directory.

```php
use React\Filesystem\Factory;
use React\Filesystem\Node\DirectoryInterface;
use React\Filesystem\Node\NodeInterface;

Factory::create()->detect(__DIR__)->then(function (DirectoryInterface $directory) {
    return $directory->ls();
})->then(static function ($nodes) {
    foreach ($nodes as $node) {
        assert($node instanceof NodeInterface);
        echo $node->name(), ': ', get_class($node), PHP_EOL;
    }
    echo '----------------------------', PHP_EOL, 'Done listing directory', PHP_EOL;
}, function (Throwable $throwable) {
    echo $throwable;
});
```

See also the [examples](examples).

## Usage

See [`Factory::create()`](#create).

### Factory

The `Factory` class exists as a convenient way to pick the best available
[filesystem implementation](#filesystem-implementations).

#### create()

The `create(): AdapterInterface` method can be used to create a new filesystem instance:

```php
$filesystem = \React\Filesystem\Factory::create();
```

This method always returns an instance implementing [`adapterinterface`](#adapterinterface),
the actual [Filesystem implementations](#filesystem-implementations) is an implementation detail.

This method can be called at any time. However, certain scheduling mechanisms are used that will make the event loop 
busier with every new instance of a filesystem adapter. To prevent that it is preferred you create it once and inject 
it where required.

### Filesystem implementations

In addition to the [`FilesystemInterface`](#filesysteminterface), there are a number of
filesystem implementations provided.

All the filesystems support these features:

* Stating a node
* Listing directory contents
* Reading/write from/to files

For most consumers of this package, the underlying filesystem implementation is
an implementation detail.
You should use the [`Factory`](#factory) to automatically create a new instance.

The factory will determine the most performant filesystem for your environment. Any extension based filesystem are 
preferred before falling back to less performant filesystems. When no extensions are detected it will fall back to 
the [`ChildProcess`](#childprocess) on Linux/Mac machines, and to an internal fallback filesystem for windows that 
uses blocking system calls. This blocking filesystem isn't documented and will be removed once 
the [`ChildProcess`](#childprocess) filesystem works on Windows. It's merely mentioned here for reference until then.

Advanced! If you explicitly need a certain filesystem implementation, you can
manually instantiate one of the following classes.
Note that you may have to install the required PHP extensions for the respective
event loop implementation first or they will throw a `BadMethodCallException` on creation.

#### ChildProcess

A [`child process`](https://reactphp.org/child-process/) based filesystem.

This uses the blocking calls like the [`file_get_contents()`](https://www.php.net/manual/en/function.file-get-contents.php)
function to do filesystem calls and is the only implementation which works out of the box with PHP.

Due to using child processes to handle filesystem calls, this filesystem the least performant is only used when no 
extensions are found to create a more performant filesystem.

#### Uv

An `ext-uv` based filesystem.

This filesystem uses the [`uv` PECL extension](https://pecl.php.net/package/uv), that
provides an interface to `libuv` library.

This filesystem is known to work with PHP 7+.

### AdapterInterface

#### detect()

The `detect(string $path): PromiseInterface<NodeInterface>` is the preferred way to get an object representing a node on the filesystem. 

When calling this method it will attempt to detect what kind of node the path is you've given it, and return an object 
implementing [`NodeInterface`](#nodeinterface). If nothing exists at the given path, a [`NotExistInterface`](#notexistinterface) object will be 
returned which you can use to create a file or directory.

#### directory()

The `directory(string $path): DirectoryInterface` creates an object representing a directory at the specified path.

Keep in mind that unlike the `detect` method the `directory` method cannot guarantee the path you pass is actually a 
directory on the filesystem and may result in unexpected behavior.

#### file()

The `file(string $path): DirectoryInterface` creates an object representing a file at the specified path.

Keep in mind that unlike the `detect` method the `file` method cannot guarantee the path you pass is actually a
file on the filesystem and may result in unexpected behavior.

### NodeInterface

The `NodeInterface` is at the core of all other node interfaces such as `FileInterface` or `DirectoryInterface`. It 
provides basic methods that are useful for all types of nodes.

#### path()

The `path(): string` method returns the path part of the node's location. So if the full path is `/path/to/file.ext` this method returns `/path/to/`.

#### name()

The `name(): string` method returns the name part of the node's location. So if the full path is `/path/to/file.ext` this method returns `file.ext`.

#### stat()

The `stat(): PromiseInterface<?Stat>` method stats the node and provides you with information such as its size, full path, create/update time.

### DirectoryInterface

#### ls

The `ls(): PromiseInterface<array<NodeInterface>>` method list all contents of the given directory and will return an
array with nodes in it. It will do it's best to detect which type a node is itself, and otherwise fallback
to `FilesystemInterface::detect(string $path): PromiseInterface<NodeInterface>`.

### FileInterface

The `*Contents` methods on this interface are designed to behave the same as PHP's `file_(get|put)_contents` functions 
as possible. Resulting in a very familiar API to read/stream from files, or write/append to a file.

#### getContents

For reading from files `getContents(int $offset = 0 , ?int $maxlen = null): PromiseInterface<string>` provides two 
arguments that control how much data it reads from the file. Without arguments, it will read everything:

```php
$file->getContents();
```

The offset and maximum length let you 'select' a chunk of the file to be read. The following will skip the first `2048` 
bytes and then read up to `1024` bytes from the file. However, if the file only contains `512` bytes  after the `2048` 
offset it will only return those `512` bytes.

```php
$file->getContents(2048, 1024);
```

It is possible to tail files with, the following example uses a timer as trigger to check for updates:

```php
$offset = 0;
Loop::addPeriodicTimer(1, function (TimerInterface $timer) use ($file, &$offset, $loop): void {
    $file->getContents($offset)->then(function (string $contents) use (&$offset, $timer, $loop): void {
        echo $contents; // Echo's the content for example purposes
        $offset += strlen($contents);
    });
});
```

#### putContents

Writing to file's is `putContents(string $contents, int $flags = 0): PromiseInterface<int>` specialty. By default, when 
passing it contents, it will truncate the file when it exists or create a new one and then fill it with the contents 
given.


```php
$file->putContents('ReactPHP');
```

Appending files is also supported, by using the `\FILE_APPEND` constant the file is appended when it exists.

```php
$file->putContents(' is awesome!', \FILE_APPEND);
```

### NotExistInterface

Both creation methods will check if the parent directory exists and create it if it doesn't. Effectively making this 
creation process recursively.

#### createDirectory

The following will create `lets/make/a/nested/directory` as a recursive directory structure.

```php
$filesystem->directory(
    __DIR__ . 'lets' . DIRECTORY_SEPARATOR . 'make' . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'directory'
)->createDirectory();
```

#### createFile

The following will create `with-a-file.txt` in `lets/make/a/nested/directory` and write `This is amazing!` into that file.

```php
use React\Filesystem\Node\FileInterface;$filesystem->file(
    __DIR__ . 'lets' . DIRECTORY_SEPARATOR . 'make' . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'directory' . DIRECTORY_SEPARATOR . 'with-a-file.txt'
)->createFile()->then(function (FileInterface $file) {
    return $file->putContents('This is amazing!')
});
```

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

Once released, this project will follow [SemVer](https://semver.org/).
At the moment, this will install the latest development version:

```bash
$ composer require react/filesystem:^0.2@dev
```

See also the [CHANGELOG](CHANGELOG.md) for details about version upgrades.

This project aims to run on any platform and thus does not require any PHP 
extensions and supports running on PHP 7.4 through current PHP 8+.
It's *highly recommended to use the latest supported PHP version* for this project.

Installing any of the event loop extensions is suggested, but entirely optional.
See also [event loop implementations](#loop-implementations) for more details.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ php vendor/bin/phpunit
```

## License

MIT, see [LICENSE file](LICENSE).
