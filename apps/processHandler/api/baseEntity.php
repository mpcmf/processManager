<?php

namespace mpcmf\apps\processHandler\api;

use mpcmf\modules\moduleBase\mappers\mapperBase;
use mpcmf\modules\moduleBase\models\modelCursor;
use mpcmf\system\validator\exception\validatorException;


abstract class baseEntity
{
    /**
     * @var $mapper mapperBase
     */
    protected $mapper;

    /**
     * @return mapperBase
     */
    abstract protected function getMapper();

    /**
     * @param modelCursor $cursor
     *
     * @return mixed
     * @throws \mpcmf\modules\moduleBase\exceptions\modelException
     */
    abstract protected function cursorToArray(modelCursor $cursor);

    public function __construct()
    {
        $this->mapper = $this->getMapper();
    }

    public function getList($offset = 0, $limit = 100, array $fields = [], array $sort = [])
    {
        $modelCursor = $this->mapper->getAllBy([], $fields, $sort);
        $modelCursor->limit($limit);
        $modelCursor->skip($offset);

        return $this->cursorToArray($modelCursor);
    }

    public function add(array $server = [])
    {
        $model = $this->mapper->getModel();

        $validationResult = $model::validate($server);
        if (!empty($validationResult['errors'])) {
            throw new validatorException($this->getErrorMessage($validationResult['errors']));
        }

        $model = $model::fromArray($server);

        $this->mapper->save($model);

        return true;
    }

    public function update($ids, array $fieldsToUpdate = [])
    {
        $model = $this->mapper->getModel();
        $validationResult = $model::validate($fieldsToUpdate, true);

        if (!empty($validationResult['errors'])) {
            throw new validatorException($this->getErrorMessage($validationResult['errors']));
        }

        $convertedFields = $this->mapper->convertDataFromForm($fieldsToUpdate);
        $this->mapper->updateAllByIds($ids, $convertedFields);

        return true;
    }

    public function getById($id)
    {
        $item = $this->mapper->getById($id)->export();
        $item['_id'] = (string) $item['_id'];

        return $item;
    }

    protected function getByCriteria(array $criteria, $offset = 0, $limit = 100, array $fields = [], array $sort = [])
    {
        $modelCursor = $this->mapper->getAllBy($criteria, $fields, $sort);
        $modelCursor->limit($limit);
        $modelCursor->skip($offset);

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
}