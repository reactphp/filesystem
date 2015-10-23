<?php

namespace React\Filesystem\ChildProcess;

use React\Promise\PromiseInterface;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Payload;
use WyriHaximus\React\ChildProcess\Messenger\Messenger;

class Process
{
    /**
     * Process constructor.
     * @param Messenger $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $messenger->registerRpc('mkdir', [$this, 'mkdir']);
        $messenger->registerRpc('rmdir', [$this, 'rmdir']);
        $messenger->registerRpc('unlink', [$this, 'unlink']);
        $messenger->registerRpc('chmod', [$this, 'chmod']);
        $messenger->registerRpc('chown', [$this, 'chown']);
        $messenger->registerRpc('stat', [$this, 'stat']);
        $messenger->registerRpc('ls', [$this, 'ls']);
        $messenger->registerRpc('rename', [$this, 'rename']);
    }

    /**
     * @param Payload $payload
     * @param Messenger $messenger
     * @return PromiseInterface
     */
    public function mkdir(Payload $payload, Messenger $messenger)
    {
        if (mkdir($payload['path'], $payload['mode'])) {
            return \React\Promise\resolve([]);
        }

        return \React\Promise\reject([]);
    }

    /**
     * @param Payload $payload
     * @param Messenger $messenger
     * @return PromiseInterface
     */
    public function rmdir(Payload $payload, Messenger $messenger)
    {
        if (rmdir($payload['path'])) {
            return \React\Promise\resolve([]);
        }

        return \React\Promise\reject([]);
    }

    /**
     * @param Payload $payload
     * @param Messenger $messenger
     * @return PromiseInterface
     */
    public function unlink(Payload $payload, Messenger $messenger)
    {
        if (unlink($payload['path'])) {
            return \React\Promise\resolve([]);
        }

        return \React\Promise\reject([]);
    }

    /**
     * @param Payload $payload
     * @param Messenger $messenger
     * @return PromiseInterface
     */
    public function chmod(Payload $payload, Messenger $messenger)
    {
        if (chmod($payload['path'], $payload['mode'])) {
            return \React\Promise\resolve([]);
        }

        return \React\Promise\reject([]);
    }

    /**
     * @param Payload $payload
     * @param Messenger $messenger
     * @return PromiseInterface
     */
    public function chown(Payload $payload, Messenger $messenger)
    {
        return \React\Promise\resolve([]);
    }

    /**
     * @param Payload $payload
     * @param Messenger $messenger
     * @return PromiseInterface
     */
    public function stat(Payload $payload, Messenger $messenger)
    {
        $stat = stat($payload['path']);
        return \React\Promise\resolve([
            'dev' => $stat['dev'],
            'ino' => $stat['ino'],
            'mode' => $stat['mode'],
            'nlink' => $stat['nlink'],
            'uid' => $stat['uid'],
            'size' => $stat['size'],
            'gid' => $stat['gid'],
            'rdev' => $stat['rdev'],
            'blksize' => $stat['blksize'],
            'blocks' => $stat['blocks'],
            'atime' => $stat['atime'],
            'mtime' => $stat['mtime'],
            'ctime' => $stat['ctime'],
        ]);
    }

    /**
     * @param Payload $payload
     * @param Messenger $messenger
     * @return PromiseInterface
     */
    public function ls(Payload $payload, Messenger $messenger)
    {
        return \React\Promise\resolve([]);
    }

    /**
     * @param Payload $payload
     * @param Messenger $messenger
     * @return PromiseInterface
     */
    public function rename(Payload $payload, Messenger $messenger)
    {
        if (rename($payload['from'], $payload['to'])) {
            return \React\Promise\resolve([]);
        }

        return \React\Promise\reject([]);
    }
}
