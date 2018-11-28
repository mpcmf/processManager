<?php

namespace mpcmf\apps\processHandler\libraries\streamRouter;

abstract class consumerBase
{
    protected $destination;

    public function __construct($destination)
    {
        $this->destination = $destination;
    }

    abstract public function consume($data);
}