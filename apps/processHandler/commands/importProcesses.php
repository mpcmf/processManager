<?php

namespace mpcmf\apps\processHandler\commands;

use mpcmf\modules\processHandler\mappers\processMapper;
use mpcmf\modules\processHandler\mappers\serverMapper;
use mpcmf\modules\processHandler\models\processModel;
use mpcmf\modules\processHandler\models\serverModel;
use mpcmf\system\application\consoleCommandBase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Ildar Saitkulov <saitkulovim@gmail.com>
 */
class importProcesses
    extends consoleCommandBase
{

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
            $jsonPath = __DIR__ . '/../processesList.json';
        }
        $processes = json_decode(file_get_contents($jsonPath), true);
        if (empty($processes)) {
            exit('Empty processes list!');
        }

        $this->saveProcesses($processes);
    }

    protected function saveProcesses($processes)
    {
        if (!is_array($processes)) {
            exit('Processes list must me array!');
        }

        $processMapper = processMapper::getInstance();
        $saved = 0;
        foreach ($processes as $process) {
            $process['server'] = 'tmpServer';
            $result = processModel::validate($process);
            unset($process['server']);
            if (!$result['status']) {
                error_log('Errors on validation fields:');
                foreach ($result['errors'] as $errors) {
                    foreach ($errors as $error) {
                        error_log($error);
                    }
                }

                var_dump($process);
                exit;
            }
            $process['server'] = $this->getServerIdByHost($process['host']);
            $processModel = processModel::fromArray($process);
            try {
                $processMapper->save($processModel);
                $saved++;
            } catch (\Exception $exception) {
                error_log("[Exception] on saving process: {$exception->getMessage()}");
            }
        }

        error_log("Created {$saved} processes!");
    }

    protected function getServerIdByHost($host)
    {
        $host = trim($host);
        $serverMapper = serverMapper::getInstance();

        try {
            /** @var serverModel $model */
            $model = $serverMapper->getBy([
                serverMapper::FIELD__HOST => $host
            ]);
        } catch (\Exception $exception) {
            if  (mb_strpos($exception->getMessage(), 'Item not found') !== false) {
                $model = serverModel::fromArray([
                    serverMapper::FIELD__HOST => $host,
                    serverMapper::FIELD__NAME => '',
                ]);
                $serverMapper->save($model);
            }

        }

        return (string) $model->getMongoId();
    }
}