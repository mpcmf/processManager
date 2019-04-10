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
            if (!isset($allServers[$process['server']]['process_count'])) {
                $allServers[$process['server']]['process_count'] = 0;
            }
            if (!isset($allServers[$process['server']]['process_count_running'])) {
                $allServers[$process['server']]['process_count_running'] = 0;
            }
            if (!isset($allServers[$process['server']]['process_count_timed_out'])) {
                $allServers[$process['server']]['process_count_timed_out'] = 0;
            }
            if (!isset($allServers[$process['server']]['forks_count_running'])) {
                $allServers[$process['server']]['forks_count_running'] = 0;
            }
            $allServers[$process['server']]['process_count']++;

            if ($process['timed_out']) {
                $allServers[$process['server']]['process_count_timed_out']++;
                
                continue;
            }
            if ($process[processMapper::FIELD__STATE] === process::STATUS__RUNNING) {
                $allServers[$process['server']]['process_count_running'] += $process[processMapper::FIELD__INSTANCES];
                $allServers[$process['server']]['forks_count_running'] += $process[processMapper::FIELD__FORKS_COUNT];
            }
        }

        foreach ($allServers as $serverKey => $server) {
            $serverMonitoringStatus = self::SERVER_STATUS_OK;
            if ($server['process_count_timed_out'] === $server['process_count']) {
                $serverMonitoringStatus = self::SERVER_STATUS_ERROR;
            } elseif($server['process_count_timed_out'] !== 0) {
                $serverMonitoringStatus = self::SERVER_STATUS_WARNING;
            }
            $allServers[$serverKey]['server_monitoring_status'] = $serverMonitoringStatus;

        }
        $this->saveStats($allServers);
    }

    protected function saveStats($servers)
    {
        $memcached = memcached::factory();
        $memcached->set($this->config['monitoring_cache_key'], $servers, $this->config['monitoring_cache_key_expire']);
    }
}