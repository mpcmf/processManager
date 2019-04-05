<?php

namespace mpcmf\apps\processHandler\libraries\api\objects;

use mpcmf\apps\processHandler\libraries\api\exceptions\validationException;
use mpcmf\apps\processHandler\libraries\api\helper;
use mpcmf\modules\moduleBase\mappers\mapperBase;
use mpcmf\modules\moduleBase\models\modelCursor;

abstract class objectBase
{
    /**
     * @var $mapper mapperBase
     */
    protected $mapper;

    /**
     * @return mapperBase
     */
    abstract protected function getMapper();

    public function __construct()
    {
        $this->mapper = $this->getMapper();
    }

    public function getList($params)
    {
        $offset = helper::getParam('offset', $params, helper::TYPE_INT, null);
        $limit = helper::getParam('limit', $params, helper::TYPE_INT, null);
        $fields = helper::getParam('fields', $params, helper::TYPE_ARRAY, []);
        $sort = helper::getParam('sort', $params, helper::TYPE_ARRAY, []);

        $modelCursor = $this->mapper->getAllBy([], $fields, $sort);
        if (is_int($limit) && $limit > 0) {
            $modelCursor->limit($limit);
        }
        if (is_int($offset) && $offset > 0) {
            $modelCursor->skip($offset);
        }


        return $this->cursorToArray($modelCursor);
    }

    public function add($params)
    {
        $object = helper::getParam('object', $params, helper::TYPE_ARRAY, []);
        $model = $this->mapper->getModel();

        $validationResult = $model::validate($object);
        if (!empty($validationResult['errors'])) {
            throw new validationException($this->getErrorMessage($validationResult['errors']));
        }

        $model = $model::fromArray($object);

        $result = $this->mapper->save($model);
        if (isset($result['upserted'])) {
            $result['upserted'] = (string) $result['upserted'];
        }

        return $result;
    }

    public function update($params)
    {
        $ids = helper::getParam('ids', $params, helper::TYPE_ARRAY);
        $fieldsToUpdate = helper::getParam('fields_to_update', $params, helper::TYPE_ARRAY);

        $model = $this->mapper->getModel();
        $validationResult = $model::validate($fieldsToUpdate, true);

        if (!empty($validationResult['errors'])) {
            throw new validationException($this->getErrorMessage($validationResult['errors']));
        }

        $convertedFields = $this->mapper->convertDataFromForm($fieldsToUpdate);
        $this->mapper->updateAllByIds($ids, $convertedFields);

        return true;
    }

    public function getById($params)
    {
        $id = helper::getParam('id', $params, helper::TYPE_STRING);
        $item = $this->mapper->getById($id)->export();
        $item['_id'] = (string) $item['_id'];

        return $item;
    }

    public function getByIds($params)
    {
        $ids = helper::getParam('ids', $params, helper::TYPE_ARRAY);
        $mongoIds = [];
        foreach ($ids as $id) {
            $mongoIds[] = new \MongoId($id);
        }

        return $this->getByCriteria(['_id' => ['$in' => $mongoIds]]);
    }

    protected function getByCriteria(array $criteria, $offset = null, $limit = null, array $fields = [], array $sort = [])
    {
        $modelCursor = $this->mapper->getAllBy($criteria, $fields, $sort);
        if (is_int($limit) && $limit > 0) {
            $modelCursor->limit($limit);
        }
        if (is_int($offset) && $offset > 0) {
            $modelCursor->skip($offset);
        }

        return $this->cursorToArray($modelCursor);
    }

    protected function getErrorMessage($errors)
    {
        $errorMessage = '';
        foreach ($errors as $errorMessages) {
            $errorMessage .= implode("\n", $errorMessages);
        }

        return $errorMessage;
    }

    /**
     * @param modelCursor $cursor
     *
     * @return array
     */
    protected function cursorToArray(modelCursor $cursor)
    {
        $result = [];
        $data = $cursor->export();
        foreach ($data as $item) {
            $item['_id'] = (string) $item['_id'];
            $result[$item['_id']] = $item;
        }

        return $result;
    }
}