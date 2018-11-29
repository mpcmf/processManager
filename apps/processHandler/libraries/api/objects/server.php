<?php

namespace mpcmf\apps\processHandler\libraries\api\objects;

use mpcmf\apps\processHandler\libraries\api\helper;
use mpcmf\modules\moduleBase\mappers\mapperBase;
use mpcmf\modules\processHandler\mappers\serverMapper;


class server
    extends objectBase
{

    /**
     * @return mapperBase
     */
    protected function getMapper()
    {
        return serverMapper::getInstance();
    }

    public function getByHost($params)
    {
        $host = helper::getParam('host', $params, helper::TYPE_STRING);

        $result = array_values($this->getByCriteria([serverMapper::FIELD__HOST => $host]));
        if (!isset($result[0])) {
            return [];
        }

        return $result[0];
    }

    public function getByName($params)
    {
        $name = helper::getParam('name', $params, helper::TYPE_STRING);

        return $this->getByCriteria([serverMapper::FIELD__NAME => $name]);
    }

    public function delete($params)
    {
        $ids = helper::getParam('ids', $params, helper::TYPE_ARRAY);

        return $this->mapper->removeAllByIds($ids);
    }
}