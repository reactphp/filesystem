<?php

namespace React\Filesystem\Pthreads;

use Threaded;
use Throwable;
use CharlotteDunois\Phoebe\Message;
use CharlotteDunois\Phoebe\Worker;

class Needle extends Threaded
{
    /**
     * The ID of the thread.
     * @var int
     */
    public $id;

    protected $path;
    protected $flags;

    /**
     * @var resource
     */
    protected static $fd;

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

    public function call(Worker $worker, $type, $args)
    {
        $args['id'] = $this->id;

        $message = new Message($type, $args);
        $worker->sendMessageToWorker($message);
    }

    public function run()
    {
        try {
            static::$fd = fopen($this->path, $this->flags);
        } catch (Throwable $e) {
            $message = new Message('rfs-fd-ready', [
                'id' => $this->id,
                'result' => null,
                'error' => Message::exportException($e),
            ]);
            return Worker::$me->sendMessageToPool($message);
        }

        $listener = function (Message $message) use (&$fd, &$listener) {
            $payload = $message->getPayload();

            $type = $message->getType();
            if ($type === 'internal-worker-exit') {
                Worker::$events->removeListener('message', $listener);

                if (static::$fd) {
                    @fclose(static::$fd);
                    static::$fd = null;
                }

                return;
            }

            if (($payload['id'] ?? null) !== $this->id) {
                return;
            }

            switch($type) {
                case 'rfs-req-read':
                    try {
                        fseek(static::$fd, $payload['offset']);
                        $chunk = fread(static::$fd, $payload['length']);

                        $message = new Message('rfs-fd-read', [
                            'id' => $this->id,
                            'result' => $chunk,
                            'error' => null,
                        ]);
                    } catch (Throwable $e) {
                        $message = new Message('rfs-fd-read', [
                            'id' => $this->id,
                            'result' => null,
                            'error' => Message::exportException($e),
                        ]);
                    }

                    Worker::$me->sendMessageToPool($message);
                    break;
                case 'rfs-req-write':
                    try {
                        fseek(static::$fd, $payload['offset']);

                        $chunk = $payload['chunk'];
                        $written = fwrite(static::$fd, $chunk, $payload['length']);

                        $message = new Message('rfs-fd-write', [
                            'id' => $this->id,
                            'result' => $written,
                            'error' => null,
                        ]);
                    } catch (Throwable $e) {
                        $message = new Message('rfs-fd-write', [
                            'id' => $this->id,
                            'result' => null,
                            'error' => Message::exportException($e),
                        ]);
                    }

                    Worker::$me->sendMessageToPool($message);
                    break;
                case 'rfs-req-close':
                    try {
                        Worker::$events->removeListener('message', $listener);

                        $closed = @fclose(static::$fd);
                        $fd = null;

                        $message = new Message('rfs-fd-close', [
                            'id' => $this->id,
                            'result' => $closed,
                            'error' => null,
                        ]);
                    } catch (Throwable $e) {
                        static::$fd = null;
                        $message = new Message('rfs-fd-close', [
                            'id' => $this->id,
                            'result' => null,
                            'error' => Message::exportException($e),
                        ]);
                    }

                    Worker::$me->sendMessageToPool($message);
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
}
