<?php
namespace React\Filesystem\ChildProcess;

use React\Filesystem\AdapterInterface;
use React\ChildProcess\Process;
use React\Promise\Deferred;

class PooledInvoker
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var int
     */
    protected $maxSimultaneousOperations = 64;

    /**
     * All the operations that are currently being performed
     * @var Process[]
     */
    protected $operations = [];

    /**
     * The command that will be called on each invoke
     * @var string
     */
    protected $command;

    /**
     * PooledInvoker Constructor
     *
     * @param AdapterInterface $adapter
     * @param int $maxSimultaneousOperations
     */
    public function __construct(AdapterInterface $adapter, $maxSimultaneousOperations = 133)
    {
        $this->loop    = $adapter->getLoop();
        $this->adapter = $adapter;
        $this->maxSimultaneousOperations = $maxSimultaneousOperations;
        $this->command = 'php '.__DIR__.'/Command.php';
    }

    /**
     * Invoke an operation in a child process
     *
     * @param  string   $op   Operation that is going to be performed
     * @param  string[] $args Arguments of the operation
     *
     * @return \React\Promise\PromiseInterface
     */
    public function invokeCall($op, $args = [])
    {
        $args     = implode(' ', $args);
        $process  = new Process($this->command.' '.$op.' '.$args);

        return $this->runProcess($process);
    }

    /**
     * Actually runs the child process and returns a promisse of its output
     *
     * @param  Process $process The process that is going to be spinned
     *
     * @return \React\Promise\PromiseInterface
     */
    public function runProcess(Process $process)
    {
        $deferred = new Deferred;

        $this->loop->addTimer(0.001, function($timer) use ($process, $deferred) {
            $process->start($timer->getLoop());

            $process->stdout->on('data', function($output) use ($deferred) {
                $deferred->resolve(unserialize($output));
            });

            $process->stderr->on('data', function($output) use ($deferred) {
                $deferred->reject($output);
            });
        });

        return $deferred->promise();
    }

    /**
     * Actually runs the child process and returns a stream of its output
     *
     * @param  Process $process The process that is going to be spinned
     *
     * @return \React\Stream\ReadableStreamInterface
     */
    public function runProcessWithOutputStream(Process $process)
    {
        $deferred = new Deferred;

        $this->loop->addTimer(0.001, function($timer) use ($process, $deferred) {
            $buffer = '';

            $process->on('exit', function($exitCode) use ($deferred, &$buffer) {
                if ($exitCode == 0) {
                    return $deferred->resolve($buffer);
                }
                $deferred->reject($output);
            });

            $process->start($timer->getLoop());

            $process->stdout->on('data', function($output) use (&$buffer) {
                $buffer += unserialize($output);
            });

            $process->stderr->on('data', function($output) use ($deferred, &$buffer) {
                $deferred->reject($output);
            });
        });

        return $process->stdout;
    }
}
