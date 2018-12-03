<?php

namespace mpcmf\apps\processHandler\libraries\api\objects;

use mpcmf\apps\processHandler\libraries\api\helper;
use mpcmf\apps\processHandler\libraries\processManager\process as processStarter;
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

    public function getByName($params)
    {
        $name = helper::getParam('name', $params, helper::TYPE_STRING);

        return $this->getByCriteria([processMapper::FIELD__NAME => $name]);
    }

    public function getByState($params)
    {
        $state = helper::getParam('state', $params, helper::TYPE_STRING);
        $offset = helper::getParam('offset', $params, helper::TYPE_INT, null);
        $limit = helper::getParam('limit', $params, helper::TYPE_INT, 100);
        $fields = helper::getParam('fields', $params, helper::TYPE_ARRAY, []);
        $sort = helper::getParam('sort', $params, helper::TYPE_ARRAY, []);

        return $this->getByCriteria([processMapper::FIELD__STATE => $state], $offset, $limit, $fields, $sort);
    }

    public function getByTags($params)
    {
        $tags = helper::getParam('tags', $params, helper::TYPE_ARRAY);
        $offset = helper::getParam('offset', $params, helper::TYPE_INT, null);
        $limit = helper::getParam('limit', $params, helper::TYPE_INT, 100);
        $fields = helper::getParam('fields', $params, helper::TYPE_ARRAY, []);
        $sort = helper::getParam('sort', $params, helper::TYPE_ARRAY, []);

        return $this->getByCriteria([
            processMapper::FIELD__TAGS => [
                '$in' => $tags
            ]
        ], $offset, $limit, $fields, $sort);
    }

    public function start($params)
    {
        $ids = helper::getParam('ids', $params, helper::TYPE_ARRAY);

        return $this->update([
            'ids' => $ids,
            'fields_to_update' => [
                processMapper::FIELD__STATE => processStarter::STATUS__RUN
            ]
        ]);
    }

    public function stop($params)
    {
        $ids = helper::getParam('ids', $params, helper::TYPE_ARRAY);

        return $this->update([
            'ids' => $ids,
            'fields_to_update' => [
                processMapper::FIELD__STATE => processStarter::STATUS__STOP
            ]
        ]);
    }

    public function restart($params)
    {
        $ids = helper::getParam('ids', $params, helper::TYPE_ARRAY);

        return $this->update([
            'ids' => $ids,
            'fields_to_update' => [
                processMapper::FIELD__STATE => processStarter::STATUS__RESTART
            ]
        ]);
    }

    public function setLogFiles($params)
    {
        $ids = helper::getParam('ids', $params, helper::TYPE_ARRAY);
        $logFiles = helper::getParam('log_files', $params, helper::TYPE_ARRAY);

        return $this->update([
            'ids' => $ids,
            'fields_to_update' => [
                processMapper::FIELD__STD_ERROR => $logFiles,
                processMapper::FIELD__STD_OUT => $logFiles
            ]
        ]);
    }

    public function getByServerId($params)
    {
        $serverId = helper::getParam('server_id', $params, helper::TYPE_STRING);
        $offset = helper::getParam('offset', $params, helper::TYPE_INT, null);
        $limit = helper::getParam('limit', $params, helper::TYPE_INT, 100);
        $fields = helper::getParam('fields', $params, helper::TYPE_ARRAY, []);
        $sort = helper::getParam('sort', $params, helper::TYPE_ARRAY, []);

        return $this->getByCriteria([processMapper::FIELD__SERVER => $serverId], $offset, $limit, $fields, $sort);
    }

    public function delete($params)
    {
        $ids = helper::getParam('ids', $params, helper::TYPE_ARRAY);

        return $this->update([
            'ids' => $ids,
            'fields_to_update' => [processMapper::FIELD__STATE => processHandler::STATE__REMOVE]
        ]);
    }
}