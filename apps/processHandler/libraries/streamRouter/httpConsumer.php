<?php

namespace mpcmf\apps\processHandler\libraries\streamRouter;

use mpcmf\system\net\reactCurl;
use React\EventLoop\LoopInterface;

class httpConsumer
    extends consumerBase
{
    protected $loop;

    public function __construct($destination, LoopInterface $loop)
    {
        parent::__construct($destination);
        $this->loop = $loop;
    }

    public function consume($data)
    {
        static $curl;

        if ($curl === null) {
            $curl = new reactCurl($this->loop);
            $curl->setSleep(0, 0, false);
            $curl->setMaxRequest(100);
        }
        $curl->prepareTask($this->destination, 'POST', $data);
        $curl->run();
    }
}