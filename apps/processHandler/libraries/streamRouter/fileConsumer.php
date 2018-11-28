<?php

namespace mpcmf\apps\processHandler\libraries\streamRouter;

class fileConsumer
    extends consumerBase
{
    public function consume($data)
    {
        file_put_contents($this->destination, $data, FILE_APPEND);
    }
}