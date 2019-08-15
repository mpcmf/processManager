<?php

namespace mpcmf\apps\processHandler\libraries\api\objects;

use mpcmf\apps\processHandler\libraries\api\helper;
use mpcmf\apps\processHandler\libraries\processManager\process as processStarter;
use mpcmf\apps\processHandler\libraries\processManager\processHandler;
use mpcmf\modules\moduleBase\mappers\mapperBase;
use mpcmf\modules\processHandler\mappers\processMapper;
use mpcmf\system\exceptions\mpcmfException;

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
        $limit = helper::getParam('limit', $params, helper::TYPE_INT, null);
        $fields = helper::getParam('fields', $params, helper::TYPE_ARRAY, []);
        $sort = helper::getParam('sort', $params, helper::TYPE_ARRAY, []);

        return $this->getByCriteria([processMapper::FIELD__STATE => $state], $offset, $limit, $fields, $sort);
    }

    public function getByTags($params)
    {
        $tags = helper::getParam('tags', $params, helper::TYPE_ARRAY);
        $offset = helper::getParam('offset', $params, helper::TYPE_INT, null);
        $limit = helper::getParam('limit', $params, helper::TYPE_INT, null);
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

    public function getByServerId($params)
    {
        $serverId = helper::getParam('server_id', $params, helper::TYPE_STRING);
        $offset = helper::getParam('offset', $params, helper::TYPE_INT, null);
        $limit = helper::getParam('limit', $params, helper::TYPE_INT, null);
        $fields = helper::getParam('fields', $params, helper::TYPE_ARRAY, []);
        $sort = helper::getParam('sort', $params, helper::TYPE_ARRAY, []);

        return $this->getByCriteria([processMapper::FIELD__SERVER => $serverId], $offset, $limit, $fields, $sort);
    }

    public function getByServerIds($params)
    {
        $serverIds = helper::getParam('server_ids', $params, helper::TYPE_ARRAY);
        $offset = helper::getParam('offset', $params, helper::TYPE_INT, null);
        $limit = helper::getParam('limit', $params, helper::TYPE_INT, null);
        $fields = helper::getParam('fields', $params, helper::TYPE_ARRAY, []);
        $sort = helper::getParam('sort', $params, helper::TYPE_ARRAY, []);

        return $this->getByCriteria([processMapper::FIELD__SERVER => ['$in' => $serverIds]], $offset, $limit, $fields, $sort);
    }

    public function delete($params)
    {
        $ids = helper::getParam('ids', $params, helper::TYPE_ARRAY);

        $mongoIds = [];
        foreach ($ids as $id) {
            $mongoIds[] = new \MongoId($id);
        }

        //if process manager stopped remove processes from db
        $processes = $this->getByCriteria([processMapper::FIELD___ID => ['$in' => $mongoIds]]);
        $idsToRemove = [];
        foreach ($processes as $process) {
            if (!is_int($process[processMapper::FIELD__UPDATED_AT]) || time() - 20 > $process[processMapper::FIELD__UPDATED_AT]) {
                $idsToRemove[] = $process['_id'];
            }
        }

        if (!empty($idsToRemove)) {
            $result = $this->mapper->removeAllByIds($idsToRemove);
            $ids = array_diff($ids, $idsToRemove);
            if (empty($ids)) {
                return $result;
            }
        }

        return $this->update([
            'ids' => $ids,
            'fields_to_update' => [processMapper::FIELD__STATE => processHandler::STATE__REMOVE]
        ]);
    }

    public function add(array $params)
    {
        if (isset($params[processMapper::FIELD__LOGGING])) {
            $params[processMapper::FIELD__LOGGING] = json_encode($params[processMapper::FIELD__LOGGING]);
        }

        $params[processMapper::FIELD__CREATED_AT] = time();

        return parent::add($params);
    }

    public function update($params)
    {
        if (isset($params['fields_to_update'][processMapper::FIELD__LOGGING])) {
            $params['fields_to_update'][processMapper::FIELD__LOGGING] = json_encode($params['fields_to_update'][processMapper::FIELD__LOGGING]);
        }

        $params['fields_to_update'][processMapper::FIELD__UPDATED_AT] = time();

        return parent::update($params);
    }

    public function copy($params)
    {
        $server = helper::getParam('server', $params, helper::TYPE_STRING);

        $errors = [];
        foreach ($this->getByIds($params) as $process) {
            unset($process[processMapper::FIELD___ID]);
            $process[processMapper::FIELD__SERVER] = $server;
            $process[processMapper::FIELD__STATE] = processHandler::STATE__STOPPED;

            try {
                $this->add(['object' => $process]);
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new mpcmfException(implode("\n", $errors));
        }

        return true;
    }

    public function move($params)
    {
        $server = helper::getParam('server', $params, helper::TYPE_STRING);

        $errors = [];
        foreach ($this->getByIds($params) as $process) {
            $processId = $process[processMapper::FIELD___ID];
            unset($process[processMapper::FIELD___ID]);
            $process[processMapper::FIELD__SERVER] = $server;

            try {
                $this->add(['object' => $process]);
                $this->delete(['ids' => [$processId]]);
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new mpcmfException(implode("\n", $errors));
        }

        return true;
    }
}