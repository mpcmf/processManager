<?php

namespace mpcmf\apps\processHandler\api;

use mpcmf\modules\moduleBase\mappers\mapperBase;
use mpcmf\modules\moduleBase\models\modelCursor;
use mpcmf\modules\processHandler\mappers\processMapper;

class process
    extends baseEntity
{

    /**
     * @return mapperBase
     */
    protected function getMapper()
    {
        return processMapper::getInstance();
    }

    public function getByName($name)
    {
        $result = array_values($this->getByCriteria([processMapper::FIELD__NAME => $name]));
        if (!isset($result[0])) {
            return [];
        }

        return $result[0];
    }

    public function getByState($state, $offset = 0, $limit = 100, array $fields = [], array $sort = [])
    {
        return $this->getByCriteria([processMapper::FIELD__STATE => $state], $offset, $limit, $fields, $sort);
    }

    public function getByTags($tags, $offset = 0, $limit = 100, array $fields = [], array $sort = [])
    {
        return $this->getByCriteria([
            processMapper::FIELD__TAGS => [
                '$in' => $tags
            ]
        ], $offset, $limit, $fields, $sort);
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
            $server = $item->getServerModel()->export();
            $server['_id'] = (string) $server['_id'];
            $data['server'] = $server;
            $result[] = $data;
        }

        return $result;
    }
}