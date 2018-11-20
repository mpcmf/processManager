<?php

namespace mpcmf\apps\processHandler\api;

use mpcmf\modules\moduleBase\mappers\mapperBase;
use mpcmf\modules\processHandler\mappers\serverMapper;


class server
    extends baseEntity
{

    public function getByByHost($host)
    {
        $result = array_values($this->getByByCriteria([serverMapper::FIELD__HOST => $host]));
        if (!isset($result[0])) {
            return [];
        }

        return $result[0];
    }

    /**
     * @return mapperBase
     */
    protected function getMapper()
    {
        return serverMapper::getInstance();
    }
}