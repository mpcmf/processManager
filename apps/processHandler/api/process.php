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
        return $this->getByCriteria([processMapper::FIELD__NAME => $name]);
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

    public function setLogFiles($ids, $logFiles)
    {
        return $this->update($ids, [
            processMapper::FIELD__STD_ERROR => $logFiles,
            processMapper::FIELD__STD_OUT => $logFiles
        ]);
    }

    public function setWsChannelIds($ids, $channelIds)
    {
        return $this->update($ids, [
            processMapper::FIELD__STD_OUT_WS_CHANNEL_ID => $channelIds,
            processMapper::FIELD__STD_ERROR_WS_CHANNEL_ID => $channelIds
        ]);
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
            $result[$data['_id']] = $data;
        }

        return $result;
    }
}