<?php

namespace mpcmf\apps\processHandler\libraries\api\objects;

use mpcmf\apps\processHandler\libraries\api\helper;
use mpcmf\modules\moduleBase\mappers\mapperBase;
use mpcmf\modules\moduleBase\models\modelCursor;
use mpcmf\system\validator\exception\validatorException;

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
        $limit = helper::getParam('limit', $params, helper::TYPE_INT, 100);
        $fields = helper::getParam('fields', $params, helper::TYPE_ARRAY, []);
        $sort = helper::getParam('sort', $params, helper::TYPE_ARRAY, []);

        $modelCursor = $this->mapper->getAllBy([], $fields, $sort);
        $modelCursor->limit($limit);
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
            throw new validatorException($this->getErrorMessage($validationResult['errors']));
        }

        $model = $model::fromArray($object);

        $this->mapper->save($model);

        return true;
    }

    public function update($params)
    {
        $ids = helper::getParam('ids', $params, helper::TYPE_ARRAY);
        $fieldsToUpdate = helper::getParam('fields_to_update', $params, helper::TYPE_ARRAY);

        $model = $this->mapper->getModel();
        $validationResult = $model::validate($fieldsToUpdate, true);

        if (!empty($validationResult['errors'])) {
            throw new validatorException($this->getErrorMessage($validationResult['errors']));
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

    protected function getByCriteria(array $criteria, $offset = null, $limit = 100, array $fields = [], array $sort = [])
    {
        $modelCursor = $this->mapper->getAllBy($criteria, $fields, $sort);
        $modelCursor->limit($limit);
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