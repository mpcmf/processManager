<?php

namespace mpcmf\apps\processHandler\libraries\processManager;

use mpcmf\modules\processHandler\mappers\serverMapper;
use mpcmf\modules\processHandler\models\serverModel;
use mpcmf\system\exceptions\mpcmfException;

class server
{

    /** @var processHandler */
    protected $processHandler;

    protected $hostname;

    /** @var serverModel */
    protected $serverModel;

    protected $pingEvery = 60;
    protected $lastPing = 0;

    public function __construct(processHandler $processHandler)
    {
        $this->processHandler = $processHandler;
        $this->hostname = gethostname();
    }

    protected function register()
    {
        try {
            /** @var serverModel $model */
            $model = $this->mapper()->getBy([
                serverMapper::FIELD__HOST => $this->hostname
            ]);
        } catch (mpcmfException $e) {
            if (mb_strpos($e->getMessage(), 'Item not found') !== 0) {
                throw $e;
            }

            $model = null;
        }

        if ($model !== null) {
            $this->serverModel = $model;
        } else {
            $this->serverModel = serverModel::fromArray([
                serverMapper::FIELD__HOST => $this->hostname,
                serverMapper::FIELD__NAME => '',
            ]);
            $this->mapper()->save($this->serverModel);
        }
        $this->processHandler->setServerId($this->serverModel->getIdValue());
        $this->ping();

        return true;
    }

    protected function ping()
    {
        if ($this->serverModel === null) {
            $this->register();
        }

        $this->serverModel->setLastPing(time());
        $this->serverModel->setCpu($this->getCpuCount());
        $this->serverModel->setRam($this->getRamSize());
        $this->serverModel->setLA(sys_getloadavg()[1]);
        $this->serverModel->setCpuUsage($this->getCpuUsage());
        $this->serverModel->setRamUsage($this->getRamUsage());

        $this->mapper()->save($this->serverModel, serverMapper::SAVE__MODE_CHANGES_ONLY);
    }

    public function mainCycle()
    {
        $now = time();
        if ($this->lastPing < $now) {
            $this->ping();
            $this->lastPing = $now + $this->pingEvery;
        }

        $this->processHandler->updateConfig();
        $this->processHandler->management();
    }

    protected function mapper()
    {
        static $mapper;

        if ($mapper === null) {
            $mapper = serverMapper::getInstance();
        }

        return $mapper;
    }

    private function getCpuCount()
    {
        $cpuCount = 1;

        if (is_file('/proc/cpuinfo')) {
            $cpuInfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuInfo, $matches);
            $cpuCount = count($matches[0]);
        }

        return $cpuCount;
    }

    /**
     * @return int RAM size in MByte
     */
    private function getRamSize()
    {
        $ramSize = 0;

        if (is_file('/proc/meminfo')) {
            $memInfo = file_get_contents('/proc/meminfo');
            preg_match('/^MemTotal:\s+(?<total>\d+)/m', $memInfo, $matches);
            $ramSize = (int)($matches['total'] / 1024);
        }

        return $ramSize;
    }

    /**
     * @return int RAM size in MByte
     */
    private function getRamUsage()
    {
        $ramUsage = 0;

        if (is_file('/proc/meminfo')) {
            $memInfo = file_get_contents('/proc/meminfo');
            preg_match('/^MemTotal:\s+(?<total>\d+)/', $memInfo, $matches);
            $ramSize = $matches['total'] / 1024;
            preg_match('/^MemFree:\s+(?<free>\d+)/m', $memInfo, $matches);
            $ramFree = $matches['free'] / 1024;

            $ramUsage = ($ramSize - $ramFree) / $ramSize;
        }

        return (int)($ramUsage * 100);
    }

    private function getCpuUsage()
    {
        $stat1 = file('/proc/stat');
        sleep(1);
        $stat2 = file('/proc/stat');

        $info1 = explode(" ", preg_replace('/cpu\s+/', '', $stat1[0]));
        $info2 = explode(" ", preg_replace('/cpu\s+/', '', $stat2[0]));
        $dif = [];

        $dif['user'] = $info2[0] - $info1[0];
        $dif['nice'] = $info2[1] - $info1[1];
        $dif['sys'] = $info2[2] - $info1[2];
        $dif['idle'] = $info2[3] - $info1[3];
        $total = array_sum($dif);
        $cpu = [];
        foreach ($dif as $type => $load) {
            $cpu[$type] = round($load / $total * 100, 1);
        }

        return $cpu['user'] + $cpu['nice'] + $cpu['sys'];
    }
}