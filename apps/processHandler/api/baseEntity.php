<?php

namespace mpcmf\apps\processHandler\api;

use mpcmf\modules\moduleBase\mappers\mapperBase;
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

    public function __construct()
    {
        $this->mapper = $this->getMapper();
    }

    public function getList($offset = 0, $limit = 100, $fields = [], $sort = [])
    {
        $cursor = $this->mapper->getAllBy([], $fields, $sort)->limit($limit)->skip($offset);

        return $this->cursorToArray($cursor);
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

    public function update(array $fieldsToUpdate = [])
    {
        if (empty($fieldsToUpdate['_id'])) {
            throw new validatorException('Empty _id field!');
        }

        $model = $this->mapper->getModel();
        $validationResult = $model::validate($fieldsToUpdate, true);

        if (!empty($validationResult['errors'])) {
            throw new validatorException($this->getErrorMessage($validationResult['errors']));
        }

        $convertedFields = $this->mapper->convertDataFromForm($fieldsToUpdate);
        $this->mapper->updateById($fieldsToUpdate['_id'], $convertedFields);

        return true;
    }

    protected function getByByCriteria(array $criteria, array $fields = [], array $sort = [])
    {
        $exportedData = $this->mapper->getAllBy($criteria, $fields, $sort)->export();

        return $this->cursorToArray($exportedData);
    }

    protected function getErrorMessage($errors)
    {
        $errorMessage = '';
        foreach ($errors as $errorMessages) {
            $errorMessage .= implode("\n", $errorMessages);
        }

        return $errorMessage;
    }

    protected function cursorToArray($cursor)
    {
        $result = [];
        foreach ($cursor as $item) {
            $item['_id'] = (string) $item['_id'];
            $result[$item['_id']] = $item;
        }

        return $result;
    }
}