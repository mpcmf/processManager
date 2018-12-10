<?php

namespace mpcmf\apps\processHandler\commands\processHandler;

use Codedungeon\PHPCliColors\Color;
use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\system\application\consoleCommandBase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class executeCommand
    extends consoleCommandBase
{

    static protected $processMethods = [
        'start' => 'start',
        'stop' => 'stop',
        'restart' => 'restart',
        'list' => 'getList'
    ];

    static protected $processStates = [
        'start' => 'run',
        'stop' => 'stop',
        'restart' => 'restart'
    ];

    static protected $expectedStates = [
        'start' => 'running',
        'stop' => 'stopped',
        'restart' => 'running'
    ];


    protected function defineArguments()
    {
        $this->addArgument('command_to_execute', InputArgument::REQUIRED, 'start|stop|restart|list');
        $this->addArgument('process_name', InputArgument::OPTIONAL, 'Process name');

        $this->addOption('hosts', null, InputOption::VALUE_OPTIONAL, 'Hosts separated by ,');
        $this->addOption('tags', null, InputOption::VALUE_OPTIONAL, 'Tags separated by ,');
        $this->addOption('allHosts', null, InputOption::VALUE_OPTIONAL, 'All hosts');
        $this->addOption('allProcesses', null, InputOption::VALUE_OPTIONAL, 'All processes');
    }

    protected function handle(InputInterface $input, OutputInterface $output)
    {
        $command = $input->getArgument('command_to_execute');
        $processName = $input->getArgument('process_name');

        $tags = $input->getOption('tags');
        $allHosts = $input->getOption('allHosts');
        $allProcesses = $input->getOption('allProcesses');
        $hostsList = $input->getOption('hosts');

        $tags = $tags ? explode(',', $tags) : [];

        $apiClient = apiClient::factory();
        $hosts = [];
        $serverIds = [];
        $serversList = [];
        if ($allHosts) {
            $result = $apiClient->call('server', 'getList', ['limit' => 200]);
            $serversList = $result['data'];
            foreach ($serversList as $server) {
                $serverIds[] = $server['_id'];
            }
        } elseif (!empty($hostsList)) {
            $hosts = explode(',', $hostsList);
        } else {
            $hosts[] = gethostname();
        }

        $processMethod = $this->getProcessMethodByCommand($command);
        if (!$processMethod) {
            echo "Unknown command {$this->getColoredText($command)}!\n";
            exit;
        }

        if (empty($serverIds)) {
            $serversList = [];
            foreach ($hosts as $host) {
                $response = $apiClient->call('server', 'getByHost', ['host' => $host]);
                if (!isset($response['data']['_id'])) {
                    echo "Server not found by host {$this->getColoredText($host)}!\n";
                    exit;
                }
                $serverIds[] = $response['data']['_id'];
                $serversList[$response['data']['_id']] = $response['data'];
            }
        }


        $processesList = $apiClient->call('process', 'getByServerIds', ['server_ids' => $serverIds, 'limit' => 3000]);

        if (empty($processesList['data'])) {
            echo "[{$this->getColoredText('FAIL')}] Not found processes on " . json_encode($hosts) . "\n";
            exit;
        }

        if ($processMethod === 'getList') {
            foreach ($processesList['data'] as $process) {
                echo helper::padding($process['name'], helper::padding($process['state'], $serversList[$process['server']]['host'], 20), 50) . PHP_EOL;
            }
            exit;
        }

        if (empty($processName) && empty($tags) && empty($allProcesses)) {
            echo "[{$this->getColoredText('ERROR')}] Please, pass argument <process name> or specify --tag=<tag> option or --allProcesses=1 \n";
            exit;
        }

        $processIds = [];
        foreach ($processesList['data'] as $process) {
            if ($allProcesses || $process['name'] === $processName || !empty(array_intersect($tags, $process['tags']))) {
                $processIds[] = $process['_id'];
            }
        }

        if (empty($processIds)) {
            $searchByTitle =  $processName ? "name {$processName}" : 'tags ' . json_encode($tags);
            echo "[{$this->getColoredText('FAIL')}] Not found processes by {$this->getColoredText($searchByTitle)} on " . json_encode($hosts) . "\n";
            exit;
        }

        $apiClient->call('process', $processMethod, ['ids' => $processIds]);

        $attempts = 30;
        $processedProcesses = [];
        $success = false;
        do {
            $result = $apiClient->call('process', 'getByIds', ['ids' => $processIds]);
            if (!$result['status']) {
                echo json_encode($result, 448) . "\n";
                exit;
            }
            $processes = $result['data'];
            $processedCount = 0;
            $notChangedStatusesCount = 0;
            foreach ($processes as $process) {
                if ($process['state'] === self::$expectedStates[$processMethod]) {
                    $processedCount++;
                    $processedProcesses[$process['_id']] = $process;
                }
                if ($process['state'] !== self::$processStates[$processMethod]) {
                    $notChangedStatusesCount++;
                }
            }
            if (count($processes) === $processedCount) {
                $success = true;
                break;
            }
            sleep(1);
        } while ($attempts--);

        foreach ($processedProcesses as $process) {
            echo '[OK] ' . helper::padding($process['name'], helper::padding($process['state'], $serversList[$process['server']]['host'], 20), 50) . PHP_EOL;
        }

        if ($success) {
            echo "[SUCCESS]\n";
            exit;
        }

        if ($notChangedStatusesCount === 0) {
            echo "[{$this->getColoredText('FAIL')}] States not changed. Probably processes manager not ran!\n";
            exit;
        }

        if ($processedCount < count($processes)) {
            foreach ($processes as $process) {
                if ($process['state'] === self::$expectedStates[$processMethod]) {
                    continue;
                }
                echo "[{$this->getColoredText('WARNING')}]  " . helper::padding($process['name'], $serversList[$process['server']]['host'] ,50) . '    Expected state:' . self::$expectedStates[$processMethod] . " current state:{$process['state']} \n";
            }
        }
        exit;
    }

    protected function getProcessMethodByCommand($command)
    {
        if (!isset(self::$processMethods[$command])) {
            return false;
        }
        return self::$processMethods[$command];
    }

    protected function getColoredText($text)
    {
        return Color::RED . $text . Color::RESET;
    }
}