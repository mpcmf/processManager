<?php

namespace mpcmf\apps\processHandler\api;

use mpcmf\modules\moduleBase\mappers\mapperBase;
use mpcmf\modules\moduleBase\models\modelCursor;
use mpcmf\modules\processHandler\mappers\serverMapper;


class server
    extends baseEntity
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

    /**
     * @param modelCursor $cursor
     *
     * @return array|mixed
     * @throws \mpcmf\modules\moduleBase\exceptions\modelException
     */
    protected function cursorToArray(modelCursor $cursor)
    {
        $result = [];
        foreach ($cursor as $item) {
            $data = $item->export();
            $data['_id'] = (string) $item->getIdValue();
            $result[$data['_id']] = $data;
        }

        return $result;
    }
}