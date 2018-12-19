<?php

namespace mpcmf\apps\processHandler\libraries\streamRouter;

use React\EventLoop\LoopInterface;

class streamRouter
{
    protected $loop;

    /**
     * @var reactStream $readableStream
     */
    protected $readableStream;

    /**
     * @var consumerBase[]
     */
    protected $consumers = [];

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * @param $destination
     *
     * @return bool
     */
    public function addConsumer($destination)
    {
        try {
            $this->consumers[$destination] = consumer::factory($destination, $this->loop);
        } catch (streamRouterException $exception) {
            error_log($exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @param $destination
     *
     * @return bool
     */
    public function removeConsumer($destination)
    {
        if (isset($this->consumers[$destination])) {
            unset($this->consumers[$destination]);
        }

        return true;
    }

    /**
     * @param array $destinations
     *
     * @return bool
     */
    public function setConsumers(array $destinations)
    {
        $tmpConsumers = [];
        foreach ($destinations as $destination) {
            try {
                $tmpConsumers[$destination] = consumer::factory($destination, $this->loop);
            } catch (streamRouterException $exception) {
                error_log($exception->getMessage());
                continue;
            }
        }
        $this->consumers = $tmpConsumers;

        return true;
    }

    /**
     * @return bool
     */
    public function removeAllConsumers()
    {
        $this->consumers = [];

        return true;
    }

    /**
     * @param $stream
     *
     * @return bool
     */
    public function run($stream)
    {
        if ($this->readableStream !== null) {
            return true;
        }

        $this->readableStream = new reactStream($stream, $this->loop);
        $this->readableStream->on('data', function ($data) {
            foreach ($this->consumers as $consumer) {
                $consumer->consume($data);
            }
        });

        return true;
    }
}