<?php

namespace mpcmf\apps\processHandler\api;

use mpcmf\modules\moduleBase\mappers\mapperBase;
use mpcmf\modules\processHandler\mappers\serverMapper;


class server
    extends baseEntity
{

    public function getByByHost($host)
    {
        $result = $this->mapper->getBy([serverMapper::FIELD__HOST => $host])->export();

        $result['_id'] = (string) $result['_id'];

        return $result;
    }

    /**
     * @return mapperBase
     */
    protected function getMapper()
    {
        return serverMapper::getInstance();
    }
}