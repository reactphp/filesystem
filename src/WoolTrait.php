<?php

namespace React\Filesystem;

use React\Promise\PromiseInterface;

trait WoolTrait
{
    protected $fd;

    /**
     * @param array $payload
     * @return PromiseInterface
     */
    public function mkdir(array $payload)
    {
        if (
            @mkdir(
                $payload['path'],
                octdec($payload['mode'])
            )
        ) {
            return \React\Promise\resolve([]);
        }

        return \React\Promise\reject([
            'error' => error_get_last(),
        ]);
    }

    /**
     * @param array $payload
     * @return PromiseInterface
     */
    public function rmdir(array $payload)
    {
        if (@rmdir($payload['path'])) {
            return \React\Promise\resolve([]);
        }

        return \React\Promise\reject([
            'error' => error_get_last(),
        ]);
    }

    /**
     * @param array $payload
     * @return PromiseInterface
     */
    public function unlink(array $payload)
    {
        if (unlink($payload['path'])) {
            return \React\Promise\resolve([]);
        }

        return \React\Promise\reject([
            'error' => error_get_last(),
        ]);
    }

    /**
     * @param array $payload
     * @return PromiseInterface
     */
    public function chmod(array $payload)
    {
        if (chmod($payload['path'], octdec($payload['mode']))) {
            return \React\Promise\resolve([]);
        }

        return \React\Promise\reject([
            'error' => error_get_last(),
        ]);
    }

    /**
     * @param array $payload
     * @return PromiseInterface
     */
    public function chown(array $payload)
    {
        if (chown($payload['path'], $payload['uid']) &&
            chgrp($payload['path'], $payload['gid'])
        ) {
            return \React\Promise\resolve([]);
        }

        return \React\Promise\reject([
            'error' => error_get_last(),
        ]);
    }

    /**
     * @param array $payload
     * @return PromiseInterface
     */
    public function stat(array $payload)
    {
        if (!file_exists($payload['path'])) {
            return \React\Promise\reject([
                'error' => ['message' => 'Path doesn\'t exist'],
            ]);
        }

        $stat = lstat($payload['path']);
        return \React\Promise\resolve([
            'dev'     => $stat['dev'],
            'ino'     => $stat['ino'],
            'mode'    => $stat['mode'],
            'nlink'   => $stat['nlink'],
            'uid'     => $stat['uid'],
            'size'    => $stat['size'],
            'gid'     => $stat['gid'],
            'rdev'    => $stat['rdev'],
            'blksize' => $stat['blksize'],
            'blocks'  => $stat['blocks'],
            'atime'   => $stat['atime'],
            'mtime'   => $stat['mtime'],
            'ctime'   => $stat['ctime'],
        ]);
    }

    /**
     * @param array $payload
     * @return PromiseInterface
     */
    public function readdir(array $payload)
    {
        $list = [];
        foreach (scandir($payload['path'], $payload['flags']) as $node) {
            $path = $payload['path'] . DIRECTORY_SEPARATOR . $node;
            if ($node == '.' || $node == '..' || (!is_dir($path) && !is_file($path))) {
                continue;
            }

            $list[] = [
                'type' => is_dir($path) ? 'dir' : 'file',
                'name' => $node,
            ];
        }
        return \React\Promise\resolve($list);
    }

    /**
     * @param array $payload
     * @return PromiseInterface
     */
    public function open(array $payload)
    {
        $this->fd = @fopen($payload['path'], $payload['flags']);
        return \React\Promise\resolve([
            'result' => (string)$this->fd,
        ]);
    }

    /**
     * @param array $payload
     * @return PromiseInterface
     */
    public function touch(array $payload)
    {
        return \React\Promise\resolve([
            touch($payload['path']) && chmod($payload['path'], octdec($payload['mode'])),
        ]);
    }

    /**
     * @param array $payload
     * @return PromiseInterface
     */
    public function read(array $payload)
    {
        fseek($this->fd, $payload['offset']);
        return \React\Promise\resolve([
            'chunk' => base64_encode(fread($this->fd, $payload['length'])),
        ]);
    }

    /**
     * @param array $payload
     * @return PromiseInterface
     */
    public function write(array $payload)
    {
        fseek($this->fd, $payload['offset']);
        return \React\Promise\resolve([
            'written' => fwrite($this->fd, base64_decode($payload['chunk']), $payload['length']),
        ]);
    }

    /**
     * @param array $payload
     * @return PromiseInterface
     */
    public function close(array $payload)
    {
        $closed = fclose($this->fd);
        $this->fd = null;
        return \React\Promise\resolve([
            $closed,
        ]);
    }

    /**
     * @param array $payload
     * @return PromiseInterface
     */
    public function rename(array $payload)
    {
        if (rename($payload['from'], $payload['to'])) {
            return \React\Promise\resolve([]);
        }

        return \React\Promise\reject([
            'error' => error_get_last(),
        ]);
    }

    /**
     * @param array $payload
     * @return PromiseInterface
     */
    public function readlink(array $payload)
    {
        return \React\Promise\resolve([
            'path' => readlink($payload['path']),
        ]);
    }

    /**
     * @param array $payload
     * @return PromiseInterface
     */
    public function symlink(array $payload)
    {
        return \React\Promise\resolve([
            'result' => symlink($payload['from'], $payload['to']),
        ]);
    }
}
