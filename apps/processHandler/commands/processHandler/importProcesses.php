<?php

namespace mpcmf\apps\processHandler\commands\processHandler;

use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\system\application\consoleCommandBase;
use mpcmf\system\cache\cache;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Ildar Saitkulov <saitkulovim@gmail.com>
 */
class importProcesses
    extends consoleCommandBase
{
    //3 weeks
    protected $cacheExpire = 1814400;

    /**
     * Define arguments
     *
     * @return mixed
     */
    protected function defineArguments()
    {
        $this->addOption('json_path', null, InputOption::VALUE_OPTIONAL, 'Path to json processes list');
    }

    protected function handle(InputInterface $input, OutputInterface $output)
    {
        $jsonPath = $input->getOption('json_path');

        if (!$jsonPath) {
            $jsonPath = __DIR__ . '/../../libraries/processesList.json';
        }
        $processes = json_decode(file_get_contents($jsonPath), true);

        if (empty($processes)) {
            exit('Empty processes list!');
        }
        $this->saveProcesses($processes);
    }

    protected function saveProcesses($processes)
    {
        $apiClient = apiClient::factory();
        foreach ($processes as $process) {
            $cacheKey = 'importProcesses_' . md5(preg_replace('/\s/', '', $process['command']) . 'import');
            if (cache::getCached($cacheKey)) {
                echo "[WARNING][{$process['command']}] already imported!\n";
                continue;
            }

            $result = $apiClient->call('process', 'add', ['object' => $process]);
            if (!$result['status']) {
                var_dump($result);
                continue;
            }
            echo "[SUCCESS][{$process['command']}]\n";
            cache::setCached($cacheKey, true, 1814400);
        }
    }
}