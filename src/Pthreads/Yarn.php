<?php

namespace React\Filesystem\Pthreads;

use CharlotteDunois\Phoebe\AsyncTask;

class Yarn extends AsyncTask
{
    protected $function;
    protected $args;

    /**
     * Yarn constructor.
     * @param string $function
     * @param array  $args Must be serializable.
     */
    public function __construct($function, $args)
    {
        parent::__construct();

        $this->function = $function;
        $this->args = serialize($args);
    }

    /**
     * @internal
     * @codeCoverageIgnore
     */
    public function run()
    {
        $this->wrap(function () {
            $args = unserialize($this->args);

            if(method_exists($this, $this->function)) {
                return call_user_func(array($this, $this->function), $args);
            }

            return call_user_func_array($this->function, $args);
        });
    }

    /**
     * @param array $payload
     * @return PromiseInterface
     * @codeCoverageIgnore
     */
    public function chown(array $payload)
    {
        return chown($payload['path'], $payload['uid']) && chgrp($payload['path'], $payload['gid']);
    }

    /**
     * @param array $payload
     * @return array
     * @codeCoverageIgnore
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
                'type' => (is_dir($path) ? 'dir' : 'file'),
                'name' => $node,
            ];
        }

        return $list;
    }

    /**
     * @param array $payload
     * @return array
     * @codeCoverageIgnore
     */
    public function stat(array $payload)
    {
        if (!file_exists($payload['path'])) {
            throw new RuntimeException('Path doesn\'t exist');
        }

        $stat = lstat($payload['path']);
        return [
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
        ];
    }

    /**
     * @return bool
     * @codeCoverageIgnore
     */
    public function touch(array $payload)
    {
        return touch($payload['path']) && chmod($payload['path'], $payload['mode']);
    }
}
