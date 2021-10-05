<?php

namespace React\Filesystem;

use React\EventLoop\ExtUvLoop;
use React\EventLoop\LoopInterface;

final class Stat
{
    private string $path;
    /** @var array<string, mixed> */
    private array $data;

    public function __construct(string $path, array $data)
    {
        $this->path = $path;
        $this->data = $data;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function mode(): ?int
    {
        return array_key_exists('mode', $this->data) ? $this->data['mode'] : null;
    }

    public function uid(): ?int
    {
        return array_key_exists('uid', $this->data) ? $this->data['uid'] : null;
    }

    public function gid(): ?int
    {
        return array_key_exists('gid', $this->data) ? $this->data['gid'] : null;
    }

    public function size(): ?int
    {
        return array_key_exists('size', $this->data) ? $this->data['size'] : null;
    }

    public function atime(): ?\DateTimeImmutable
    {
        return array_key_exists('atime', $this->data) ? new \DateTimeImmutable('@' . $this->data['atime']) : null;
    }

    public function mtime(): ?\DateTimeImmutable
    {
        return array_key_exists('mtime', $this->data) ? new \DateTimeImmutable('@' . $this->data['mtime']) : null;
    }

    public function ctime(): ?\DateTimeImmutable
    {
        return array_key_exists('ctime', $this->data) ? new \DateTimeImmutable('@' . $this->data['ctime']) : null;
    }
}
