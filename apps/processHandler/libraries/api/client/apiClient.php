<?php

namespace mpcmf\apps\processHandler\libraries\api\client;


use mpcmf\system\configuration\config;

class apiClient
{
    protected static $instances = [];

    protected function __construct()
    {
    }

    /**
     * @param string $client
     *
     * @return client
     */
    public static function factory($client = 'native')
    {
        if (!isset(self::$instances[$client])) {
            $clientName = config::getConfig(__CLASS__)[$client];
            self::$instances[$client] = new $clientName();
        }

        return self::$instances[$client];
    }
}