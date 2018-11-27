<?php

namespace mpcmf\apps\processHandler\libraries\api;

use mpcmf\apps\processHandler\libraries\processManager\processHandler;
use mpcmf\modules\moduleBase\mappers\mapperBase;
use mpcmf\modules\processHandler\mappers\processMapper;

class process
    extends objectBase
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

    public function getByServerId($serverId, $offset = 0, $limit = 100, array $fields = [], array $sort = [])
    {
        return $this->getByCriteria([processMapper::FIELD__SERVER => $serverId], $offset, $limit, $fields, $sort);
    }

    public function delete($ids)
    {
        return $this->update($ids, [processMapper::FIELD__STATE => processHandler::STATE__REMOVE]);
    }
}