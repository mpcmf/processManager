<?php

namespace mpcmf\apps\processHandler\libraries\api\client;

interface client
{
    public function call($object, $method, array $params = []);
}