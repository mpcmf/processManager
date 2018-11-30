<?php

namespace mpcmf\apps\processHandler\libraries\api\client;

use mpcmf\system\helper\io\codes;
use mpcmf\system\helper\io\response;

class native
    implements client
{
    use response;

    public function call($object, $method, array $params = [])
    {
        $class = "mpcmf\\apps\\processHandler\\libraries\api\\objects\\{$object}";;

        if (!class_exists($class)) {
            return self::error([
                'errors' => [
                    "Api object {$object} doesn't exists!"
                ]
            ], codes::CODE_ERR_OBJECT_NOT_FOUND, codes::RESPONSE_CODE_NOT_FOUND);
        }

        $apiObject = new $class();
        if (!method_exists($apiObject, $method)) {
            return self::error([
                'errors' => [
                    "Api method {$object}.{$method} doesn't exists!"
                ]
            ], codes::CODE_ERR_METHOD_NOT_IMPLEMENTED, codes::RESPONSE_CODE_NOT_FOUND);
        }

        try {
            return self::success($apiObject->$method($params), codes::RESPONSE_CODE_OK);
        } catch (\Exception $exception) {
            return self::errorByException($exception);
        }
    }
}