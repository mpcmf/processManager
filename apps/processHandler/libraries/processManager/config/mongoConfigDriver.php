<?php

namespace mpcmf\apps\processHandler\libraries\processManager\config;

use mpcmf\system\storage\mongoInstance;

class mongoConfigDriver
    implements configDriverInterface
{

    protected $driverConfig = [
        'section' => 'localhost',
        'db' => 'processManager',
        'collection' => 'process'
    ];

    public function update()
    {
        $collection = $this->collection();

        $cursor = $collection->find([]);

        $config = [];
        foreach ($cursor as $item) {
            $config[$item['tag']] = $item;
        }

        return $config;
    }

    protected function collection()
    {
        static $collection;

        if ($collection === null) {
            $db = mongoInstance::factory($this->driverConfig['section']);
            $collection = $db->getCollection($this->driverConfig['db'], $this->driverConfig['collection']);
        }

        return $collection;
    }
}