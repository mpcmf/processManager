<?php

namespace mpcmf\apps\processHandler\libraries\processManager\config;

use mpcmf\modules\processHandler\mappers\processMapper;
use mpcmf\modules\processHandler\models\processModel;

class configStorage
{

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

        $config = [];
        /** @var processModel $item */
        foreach ($cursor as $item) {
            $config[(string) $item->getIdValue()] = $item;
        }

        return $config;
    }

    public function saveConfig(processModel $process)
    {
        $process->setLastUpdate(time());
        $this->mapper()->save($process);
    }

    public function removeConfig(processModel $process)
    {
        $this->mapper()->remove($process);
    }
}