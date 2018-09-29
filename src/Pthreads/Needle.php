<?php

namespace React\Filesystem\Pthreads;

use Threaded;
use Throwable;
use CharlotteDunois\Phoebe\Message;
use CharlotteDunois\Phoebe\Worker;

class Needle extends Threaded
{
    protected $id;
    protected $path;
    protected $flags;

    /**
     * Needle constructor.
     * @param string $path
     * @param mixed  $flags
     */
    public function __construct($path, $flags)
    {
        $this->id = bin2hex(random_bytes(10));
        $this->path = $path;
        $this->flags = $flags;
    }

    /**
     * Gets the ID of the thread.
     * @return string
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * @internal
     */
    public function run()
    {
        static $fd;

        try {
            $fd = fopen($this->path, $this->flags);
        } catch (Throwable $e) {
            $message = new Message('rfs-fd-ready', [
                'id' => $this->id,
                'result' => null,
                'error' => Message::exportException($e),
            ]);
            return Worker::$me->sendMessageToPool($message);
        }

        // Workaround for an unexpected behaviour (bug?) which
        // makes the needle object lose all properties
        // https://hastebin.com/jibujukoco.rb
        $id = $this->id;

        $listener = function (Message $message) use ($id, &$fd, &$listener) {
            $payload = $message->getPayload();

            $type = $message->getType();
            if ($type === 'internal-worker-exit') {
                Worker::$events->removeListener('message', $listener);

                if ($fd) {
                    @fclose($fd);
                    $fd = null;
                }

                return;
            }

            if (($payload['id'] ?? null) !== $id) {
                return;
            }

            switch($type) {
                case 'rfs-req-read':
                    $this->handleReadCall($id, $fd, $payload);
                    break;
                case 'rfs-req-write':
                    $this->handleWriteCall($id, $fd, $payload);
                    break;
                case 'rfs-req-close':
                    $this->handleCloseCall($id, $fd, $listener);
                    break;
            }
        };

        Worker::$events->on('message', $listener);

        $message = new Message('rfs-fd-ready', [
            'id' => $this->id,
            'result' => null,
            'error' => null,
        ]);
        Worker::$me->sendMessageToPool($message);
    }

    /**
     * Handles messages for reading.
     * @internal
     */
    protected function handleReadCall(string $id, &$fd, array $payload)
    {
        try {
            fseek($fd, $payload['offset']);
            $chunk = fread($fd, $payload['length']);

            $message = new Message('rfs-fd-read', [
                'id' => $id,
                'result' => $chunk,
                'error' => null,
            ]);
        } catch (Throwable $e) {
            $message = new Message('rfs-fd-read', [
                'id' => $id,
                'result' => null,
                'error' => Message::exportException($e),
            ]);
        }

        Worker::$me->sendMessageToPool($message);
    }

    /**
     * Handles messages for writing.
     * @internal
     */
    protected function handleWriteCall(string $id, &$fd, array $payload)
    {
        try {
            fseek($fd, $payload['offset']);

            $chunk = $payload['chunk'];
            $written = fwrite($fd, $chunk, $payload['length']);

            $message = new Message('rfs-fd-write', [
                'id' => $id,
                'result' => $written,
                'error' => null,
            ]);
        } catch (Throwable $e) {
            $message = new Message('rfs-fd-write', [
                'id' => $id,
                'result' => null,
                'error' => Message::exportException($e),
            ]);
        }

        Worker::$me->sendMessageToPool($message);
    }

    /**
     * Handles messages for closing.
     * @internal
     */
    protected function handleCloseCall(string $id, &$fd, callable $listener)
    {
        try {
            Worker::$events->removeListener('message', $listener);

            $closed = @fclose($fd);
            $fd = null;

            $message = new Message('rfs-fd-close', [
                'id' => $id,
                'result' => $closed,
                'error' => null,
            ]);
        } catch (Throwable $e) {
            $fd = null;
            $message = new Message('rfs-fd-close', [
                'id' => $id,
                'result' => null,
                'error' => Message::exportException($e),
            ]);
        }

        Worker::$me->sendMessageToPool($message);
    }
}
