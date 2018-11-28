<?php

namespace mpcmf\apps\processHandler\commands\processHandler;

use mpcmf\modules\processHandler\mappers\processMapper;
use mpcmf\modules\processHandler\mappers\serverMapper;
use mpcmf\modules\processHandler\models\processModel;
use mpcmf\system\validator\exception\validatorException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Ildar Saitkulov <saitkulovim@gmail.com>
 */
class createProcesses
    extends importProcesses
{

    /**
     * Define arguments
     *
     * @return mixed
     */
    protected function defineArguments()
    {

    }

    protected function handle(InputInterface $input, OutputInterface $output)
    {
        $this->runInteractiveCreateProcess();
    }

    protected function runInteractiveCreateProcess()
    {
        $hosts = $this->getAllHostsList();

        $optionsMenu = ['new_server' => 'Add new server'];
        foreach ($hosts as $itemId => $host) {
            $optionsMenu[$itemId] = $host[serverMapper::FIELD__HOST];
        }

        $option = \cli\menu($optionsMenu, null, 'Choose server');
        if ($option === 'new_server') {
            $host = trim(readline('Enter host: '));
            $serverId = $this->getServerIdByHost($host);
            echo "New server created {$host}\n";
        } else {
            $serverId = $option;
        }

        $enterAllFieldsMenu = \cli\menu(['yes' => 'yes', 'no' => 'no, I want enter all fields'], 'yes', 'Do you want enter only required fields?');
        $enterAllFields = $enterAllFieldsMenu === 'yes';

        $processMapper = processMapper::getInstance();
        /** @var processModel $model */
        $model = $processMapper->getModel();
        $model->setServer($serverId);
        $map = $processMapper->getMap();

        foreach ($map as $fieldName => $mapField) {
            if (($enterAllFields && !$mapField['options']['required']) || $fieldName === processMapper::FIELD__SERVER || $fieldName === processMapper::FIELD___ID || $fieldName === processMapper::FIELD__LAST_UPDATE) {
                continue;
            }

            do {
                $input = trim(readline("{$fieldName}: "));
                if (!$mapField['options']['required'] && empty($input)) {
                    break;
                }

                if ($mapField['options']['required'] && empty($input)) {
                    error_log('Empty input!');
                    $success = false;

                    continue;
                }

                try {
                    if ($mapField['type'] === 'string') {
                        $model->{$mapField['setter']}($input);
                    } elseif ($mapField['type'] === 'int') {
                        $model->{$mapField['setter']}((int) $input);
                    } elseif ($mapField['type'] === 'string[]') {
                        $model->{$mapField['setter']}([$input]);
                    }
                    $success = true;
                } catch (validatorException $exception) {
                    error_log($exception->getMessage());
                    $success = false;
                }
            } while (!$success);
        }

        $processMapper->save($model);

        echo "---------------------------\n";
        echo "Process saved successfully!\n";
        echo "---------------------------\n";

        $createAnotherProcess = \cli\menu(['yes' => 'yes', 'exit' => 'exit'], 'yes', 'Do you want create another process?');

        if ($createAnotherProcess === 'yes') {
            $this->runInteractiveCreateProcess();
        }
    }

    protected function getAllHostsList()
    {
        $serverMapper = serverMapper::getInstance();

        return $serverMapper->searchAllByCriteria([], [serverMapper::FIELD__HOST,])->export();
    }
}