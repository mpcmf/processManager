<?php

namespace mpcmf\apps\processHandler\commands\processHandler;

use Codedungeon\PHPCliColors\Color;
use mpcmf\apps\processHandler\libraries\api\client\apiClient;
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
        'restart' => 'restart'
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
        $this->addArgument('command_to_execute', InputArgument::REQUIRED, 'start|stop|restart');
        $this->addArgument('process_name', InputArgument::REQUIRED, 'Process name');
        $this->addArgument('hosts', InputArgument::OPTIONAL, 'host names separated by|');

        //$this->addOption('all', 'a', InputOption::VALUE_OPTIONAL, 'all processes', true);
    }

    protected function handle(InputInterface $input, OutputInterface $output)
    {
        $command = $input->getArgument('command_to_execute');
        $processName = $input->getArgument('process_name');
        $hostsList = $input->getArgument('hosts');

        $hosts = [];
        if (!empty($hostsList)) {
            $hosts = explode('|', $hostsList);
        }

        if (empty($hosts)) {
            $hosts[] = gethostname();
        }
        $processMethod = $this->getProcessMethodByCommand($command);
        if (!$processMethod) {
            echo "Unknown command {$this->getColoredText($command)}!\n";
            exit;
        }

        $apiClient = apiClient::factory();

        $serverIds = [];
        foreach ($hosts as $host) {
            $response = $apiClient->call('server', 'getByHost', ['host' => $host]);
            if (!isset($response['data']['_id'])) {
                echo "Server not found by host {$this->getColoredText($host)}!\n";
                exit;
            }
            $serverIds[] = $response['data']['_id'];
        }


        $processesList = $apiClient->call('process', 'getByServerIds', ['server_ids' => $serverIds, 'limit' => 500]);

        if (empty($processesList['data'])) {
            echo "[{$this->getColoredText('FAIL')}] Not found processes on " . json_encode($hosts) . "\n";
            exit;
        }

        $processIds = [];
        foreach ($processesList['data'] as $process) {
            if ($process['name'] === $processName) {
                $processIds[] = $process['_id'];
            }
        }

        if (empty($processIds)) {
            echo "[{$this->getColoredText('FAIL')}] Not found processes by name {$this->getColoredText($processName)} on " . json_encode($hosts) . "\n";
            exit;
        }

        $apiClient->call('process', $processMethod, ['ids' => $processIds]);

        $attempts = 20;
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
                }
                if ($process['state'] !== self::$processStates[$processMethod]) {
                    $notChangedStatusesCount++;
                }
            }
            if (count($processes) === $processedCount) {
                echo "[OK]\n";
                exit;
            }
            sleep(1);
        } while ($attempts--);

        if ($notChangedStatusesCount === 0) {
            echo "[{$this->getColoredText('FAIL')}] States not changed. Probably processes manager not ran!\n";
            exit;
        }

        if ($processedCount < count($processes)) {
            foreach ($processes as $process) {
                if ($process['state'] === self::$expectedStates[$processMethod]) {
                    continue;
                }
                echo "[{$this->getColoredText('WARNING')}] [{$process['name']}] Expected state:" . self::$expectedStates[$processMethod] . " current state:{$process['state']} \n";
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