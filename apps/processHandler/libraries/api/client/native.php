<?php

namespace mpcmf\apps\processHandler\libraries\api\client;

class native
    implements client
{

    public function call($object, $method, array $params = [])
    {
        $class = "\\mpcmf\\apps\\processHandler\\api\\{$object}";

        if (!class_exists($class)) {
            return 'todo generate error!';
        }

        $apiClass = new $class();
        $apiClass->$method($params);
    }
}