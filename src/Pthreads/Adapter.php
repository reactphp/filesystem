<?php

namespace React\Filesystem\Pthreads;

use React\EventLoop\LoopInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\CallInvokerInterface;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\MappedTypeDetector;
use React\Filesystem\TypeDetectorInterface;
use React\Filesystem\ModeTypeDetector;
use React\Filesystem\OpenFileLimiter;
use React\Promise\Deferred;

class Adapter implements AdapterInterface
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var Worker[]
     */
    protected $threads = [];

    /**
     * @var CallInvokerInterface
     */
    protected $invoker;

    /**
     * @var OpenFileLimiter
     */
    protected $openFileLimiter;

    /**
     * @var TypeDetectorInterface[]
     */
    protected $typeDetectors = [];

    /**
     * @inheritDoc
     */
    public function __construct(LoopInterface $loop, array $options = [])
    {
        $this->loop = $loop;
        $this->invoker = \React\Filesystem\getInvoker($this, $options, 'invoker', 'React\Filesystem\InstantInvoker');
        $this->openFileLimiter = new OpenFileLimiter(\React\Filesystem\getOpenFileLimit($options));
    }

    /**
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * {@inheritDoc}
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;

        $this->typeDetectors = [
            MappedTypeDetector::createDefault($this->filesystem),
            new ModeTypeDetector($this->filesystem),
        ];
    }

    /**
     * @param CallInvokerInterface $invoker
     * @return void
     */
    public function setInvoker(CallInvokerInterface $invoker)
    {
        $this->invoker = $invoker;
    }

    /**
     * @inheritDoc
     */
    public function callFilesystem($function, $args, $errorResultCode = -1)
    {
        return (new Yarn(new Call($function, $args)))->call();
    }

    /**
     * @inheritDoc
     */
    public function mkdir($path, $mode = self::CREATION_MODE)
    {
        // TODO: Implement mkdir() method.
    }

    /**
     * @inheritDoc
     */
    public function rmdir($path)
    {
        // TODO: Implement rmdir() method.
    }

    /**
     * @inheritDoc
     */
    public function unlink($filename)
    {
        // TODO: Implement unlink() method.
    }

    /**
     * @inheritDoc
     */
    public function chmod($path, $mode)
    {
        // TODO: Implement chmod() method.
    }

    /**
     * @inheritDoc
     */
    public function chown($path, $uid, $gid)
    {
        // TODO: Implement chown() method.
    }

    /**
     * @inheritDoc
     */
    public function stat($filename)
    {
        return $this->invoker->invokeCall('stat', [
            $filename,
        ])->then(function ($stat) {
            $stat['atime'] = new \DateTime('@' .$stat['atime']);
            $stat['mtime'] = new \DateTime('@' .$stat['mtime']);
            $stat['ctime'] = new \DateTime('@' .$stat['ctime']);
            return \React\Promise\resolve($stat);
        });
    }

    /**
     * @inheritDoc
     */
    public function ls($path, $flags = EIO_READDIR_DIRS_FIRST)
    {
        // TODO: Implement ls() method.
    }

    /**
     * @inheritDoc
     */
    public function touch($path, $mode = self::CREATION_MODE)
    {
        // TODO: Implement touch() method.
    }

    /**
     * @inheritDoc
     */
    public function open($path, $flags, $mode = self::CREATION_MODE)
    {
        // TODO: Implement open() method.
    }

    /**
     * @inheritDoc
     */
    public function read($fileDescriptor, $length, $offset)
    {
        // TODO: Implement read() method.
    }

    /**
     * @inheritDoc
     */
    public function write($fileDescriptor, $data, $length, $offset)
    {
        // TODO: Implement write() method.
    }

    /**
     * @inheritDoc
     */
    public function close($fd)
    {
        // TODO: Implement close() method.
    }

    /**
     * @inheritDoc
     */
    public function rename($fromPath, $toPath)
    {
        // TODO: Implement rename() method.
    }

    /**
     * @inheritDoc
     */
    public function readlink($path)
    {
        // TODO: Implement readlink() method.
    }

    /**
     * @inheritDoc
     */
    public function symlink($fromPath, $toPath)
    {
        // TODO: Implement symlink() method.
    }

    /**
     * @inheritDoc
     */
    public function detectType($path)
    {
        return \React\Filesystem\detectType($this->typeDetectors, [
            'path' => $path,
        ]);
    }
}
