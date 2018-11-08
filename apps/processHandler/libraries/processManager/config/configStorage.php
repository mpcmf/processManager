<?php

namespace mpcmf\apps\processHandler\libraries\processManager\config;

use mpcmf\modules\processHandler\mappers\processMapper;
use mpcmf\modules\processHandler\models\processModel;

class configStorage
{
    /** @var processModel[] */
    protected $config = [];

    protected function mapper()
    {
        static $mapper;

        if ($mapper === null) {
            $mapper = processMapper::getInstance();
        }

        return $mapper;
    }

    public function getProcessesConfig($serverId)
    {
        $criteria = $this->mapper()->convertDataFromForm([
            processMapper::FIELD__SERVER => $serverId
        ]);

        $cursor = $this->mapper()->getAllBy($criteria);

        /** @var processModel $item */
        foreach ($cursor as $item) {
            $this->config[(string) $item->getIdValue()] = $item;
        }

        return $this->config;
    }

    public function saveConfig(processModel $process)
    {
        $this->mapper()->save($process);
    }
}