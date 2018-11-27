<?php

namespace mpcmf\apps\processHandler\api;

use mpcmf\modules\moduleBase\mappers\mapperBase;
use mpcmf\modules\processHandler\mappers\serverMapper;


class server
    extends objectBase
{

    public function getByHost($host)
    {
        $result = array_values($this->getByCriteria([serverMapper::FIELD__HOST => $host]));
        if (!isset($result[0])) {
            return [];
        }

        return $result[0];
    }

    public function getByName($name)
    {
        return $this->getByCriteria([serverMapper::FIELD__NAME => $name]);
    }

    /**
     * @return mapperBase
     */
    protected function getMapper()
    {
        return serverMapper::getInstance();
    }

    public function delete($ids)
    {
        return $this->mapper->removeAllByIds($ids);
    }
}