ReactFilesystem
===============

React WIP for [EIO](http://php.net/eio), keep in mind that this can be very unstable at times and is not stable by a long shot.

## Goal ##

The goal of this repo is to become `React\Filesystem`, hence the naming and namespaces.

## Examples ##

`Adding examples here over time.`

## Reading files ##

```php
$filesystem->getContents('test.txt')->then(function($contents) {
});
```

Which is a convenience method for:

```php
$filesystem->open('test.txt')->then(function($stream) {
    return React\Stream\BufferedSink::createPromise($stream);
})->then(function($contents) {
});
```

Which in it's turn is a convenience method for:

```php
$filesystem->file('test.txt')->open(EIO_O_RDONLY)->then(function ($stream) use ($node) {
    $buffer = '';
    $deferred = new \React\Promise\Deferred();
    $stream->on('data', function ($data) use (&$buffer) {
        $buffer += $data;
    });
    $stream->on('end', function ($data) use ($stream, $deferred, &$buffer) {
        $stream->close();
        $deferred->resolve(&$buffer);
    });
    return $deferred->promise();
});
```

## Writing files ##

Not possible yet
