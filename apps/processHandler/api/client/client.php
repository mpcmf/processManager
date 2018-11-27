<?php

namespace mpcmf\apps\processHandler\api\client;

interface client
{
    public function call($object, $method, array $params = []);
}