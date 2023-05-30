<?php

namespace React\Filesystem\Eio;

/**
 * Singleton to make sure we always only have one file descriptor for the ext-eio event stream.
 * Creating more than one will invalidate the previous ones and make anything still using those fail.
 *
 * @internal
 */
final class EventStream
{
    private static $fd = null;

    public static function get()
    {
        if (self::$fd !== null) {
            return self::$fd;
        }

        self::$fd = eio_get_event_stream();

        return self::$fd;
    }
}
