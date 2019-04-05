<?php

namespace mpcmf\apps\processHandler\commands\processHandler;

use mpcmf\apps\processHandler\libraries\api\client\apiClient;
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
    const SERVER_STATUS_OK = 'ok';
    const SERVER_STATUS_WARNING = 'warning';
    const SERVER_STATUS_ERROR = 'error';

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
                processMapper::FIELD__LAST_UPDATE => 1
            ]
        ])['data'];


        foreach ($allProcesses as $process) {
            $allServers[$process['server']]['processes'][$process['_id']]['timed_out'] = $currentTime - $process[processMapper::FIELD__LAST_UPDATE] > $this->config['process_time_out'];
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
        }

        $this->saveStats($allServers);
    }

    protected function saveStats($servers)
    {
        $memcached = memcached::factory();
        $memcached->set($this->config['monitoring_cache_key'], $servers, $this->config['monitoring_cache_key_expire']);
    }
}