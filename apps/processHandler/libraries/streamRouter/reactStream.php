<?php

namespace mpcmf\apps\processHandler\libraries\streamRouter;

use InvalidArgumentException;
use React\EventLoop\LoopInterface;
use React\Stream\Buffer;
use React\Stream\Stream;
use React\Stream\WritableStreamInterface;

class reactStream
    extends Stream
{
    public function __construct($stream, LoopInterface $loop, WritableStreamInterface $buffer = null)
    {
        $this->stream = $stream;
        if (!is_resource($this->stream) || get_resource_type($this->stream) !== "stream") {
            throw new InvalidArgumentException('First parameter must be a valid stream resource');
        }

        stream_set_blocking($this->stream, 0);

        if (function_exists('stream_set_read_buffer') && !$this->isLegacyPipe($stream)) {
            stream_set_read_buffer($this->stream, 0);
        }

        if ($buffer === null) {
            $buffer = new Buffer($stream, $loop);
        }

        $this->loop = $loop;
        $this->buffer = $buffer;

        $that = $this;

        $this->buffer->on('error', function ($error) use ($that) {
            $that->emit('error', array($error, $that));
        });

        $this->buffer->on('close', array($this, 'close'));

        $this->buffer->on('drain', function () use ($that) {
            $that->emit('drain', array($that));
        });

        $this->resume();
    }

    private function isLegacyPipe($resource)
    {
        if (PHP_VERSION_ID < 50428 || (PHP_VERSION_ID >= 50500 && PHP_VERSION_ID < 50512)) {
            $meta = stream_get_meta_data($resource);
            if (isset($meta['stream_type']) && $meta['stream_type'] === 'STDIO') {
                return true;
            }
        }
        return false;
    }

}