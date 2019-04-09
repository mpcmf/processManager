<?php

namespace mpcmf\apps\processHandler\commands\processHandler;

use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\processManager\process;
use mpcmf\modules\processHandler\mappers\processMapper;
use mpcmf\modules\processHandler\mappers\serverMapper;
use mpcmf\system\application\consoleCommandBase;
use mpcmf\system\cache\memcached;
use mpcmf\system\configuration\config;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class exporter
    extends consoleCommandBase
{
    const SERVER_STATUS_OK = 0;
    const SERVER_STATUS_WARNING = 1;
    const SERVER_STATUS_ERROR = 2;

    protected $config;

    protected function defineArguments()
    {

    }

    protected function handle(InputInterface $input, OutputInterface $output)
    {
        $this->config = config::getConfig(__CLASS__);
        for(;;) {
            self::log()->addInfo('Starting saving stats!');
            $this->main();
            self::log()->addInfo('Stats saved!');
            self::log()->addInfo("Sleeping {$this->config['sleep']} sec...");
            sleep($this->config['sleep']);
        }
    }

    protected function main()
    {
        static $apiClient;

        if ($apiClient === null) {
            $apiClient = apiClient::factory();
        }

        $allServers = $apiClient->call('server', 'getList', [
            'fields' => [
                serverMapper::FIELD__HOST => 1
            ]
        ])['data'];

        $currentTime = time();
        $allProcesses = $apiClient->call('process', 'getList', [
            'fields' => [
                processMapper::FIELD__NAME => 1,
                processMapper::FIELD__SERVER => 1,
                processMapper::FIELD__LAST_UPDATE => 1,
                processMapper::FIELD__FORKS_COUNT => 1,
                processMapper::FIELD__INSTANCES => 1,
                processMapper::FIELD__STATE => 1,
            ]
        ])['data'];


        foreach ($allProcesses as $process) {
            $process['timed_out'] = $currentTime - $process[processMapper::FIELD__LAST_UPDATE] > $this->config['process_time_out'];
            $allServers[$process['server']]['processes'][$process['_id']] = $process;
        }

        foreach ($allServers as $serverKey => $server) {
            $timedOut = array_column($server['processes'], 'timed_out');
            $serverMonitoringStatus = self::SERVER_STATUS_OK;
            if (in_array(true, $timedOut, true)) {
                $serverMonitoringStatus = self::SERVER_STATUS_ERROR;
                if (in_array(false, $timedOut, true)) {
                    $serverMonitoringStatus = self::SERVER_STATUS_WARNING;
                }
            }
            $allServers[$serverKey]['server_monitoring_status'] = $serverMonitoringStatus;

            $processCount = 0;
            $forksCount = 0;
            if ($allServers[$serverKey]['server_monitoring_status'] === self::SERVER_STATUS_OK || $allServers[$serverKey]['server_monitoring_status'] === self::SERVER_STATUS_WARNING) {
                foreach ($server['processes'] as $process) {
                    if ($process[processMapper::FIELD__STATE] === process::STATUS__RUNNING && !$process['timed_out']) {
                        $processCount += $process[processMapper::FIELD__INSTANCES];
                        $forksCount += $process[processMapper::FIELD__FORKS_COUNT];
                    }
                }
            }
            $allServers[$serverKey]['process_count'] = $processCount;
            $allServers[$serverKey]['forks_count'] = $forksCount;
        }
        $this->saveStats($allServers);
    }

    protected function saveStats($servers)
    {
        $memcached = memcached::factory();
        $memcached->set($this->config['monitoring_cache_key'], $servers, $this->config['monitoring_cache_key_expire']);
    }
}