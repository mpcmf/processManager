<?php

namespace mpcmf\apps\processHandler\libraries\stats;


use mpcmf\modules\processHandler\mappers\statsMapper;
use mpcmf\modules\processHandler\models\statsModel;
use mpcmf\system\helper\io\log;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class stats
{
    use log;

    public static function start($processCommand, $processMode, $instancesCount, $serverName)
    {
        $statsModel = self::buildModel($processCommand, $processMode, $instancesCount, $serverName);
        $statsModel->setActionType(statsMapper::ACTION_TYPE__START);
        self::save($statsModel);
    }

    public static function stop($processCommand, $processMode, $instancesCount, $serverName)
    {
        $statsModel = self::buildModel($processCommand, $processMode, $instancesCount, $serverName);
        $statsModel->setActionType(statsMapper::ACTION_TYPE__STOP);
        self::save($statsModel);
    }

    private static function buildModel($processCommand, $processMode, $instancesCount, $serverName)
    {
        $statsModel = new statsModel();
        $statsModel->setProcessCommand($processCommand);
        $statsModel->setProcessMode($processMode);
        $statsModel->setProcessInstances($instancesCount);
        $statsModel->setServer($serverName);

        return $statsModel;
    }

    private static function save(statsModel $stats)
    {
        try {
            $stats->setActionAt(time());
            $mapper = statsMapper::getInstance();
            $mapper->save($stats);
        } catch (\Exception $e) {
            self::log()->warning("Unable to save stats: {$e->getMessage()}");
        }
    }
}