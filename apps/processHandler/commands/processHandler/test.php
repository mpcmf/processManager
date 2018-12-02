<?php

namespace mpcmf\apps\processHandler\commands\processHandler;

use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\api\client\native;
use mpcmf\apps\processHandler\libraries\cliMenu\itemFilter;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;
use mpcmf\system\application\consoleCommandBase;
use React\EventLoop\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use mpcmf\apps\processHandler\libraries\processManager\process;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class test
    extends consoleCommandBase
{
    /**
     * Define arguments
     *
     * @return mixed
     */
    protected function defineArguments()
    {
        // TODO: Implement defineArguments() method.
    }


    protected function handle(InputInterface $input, OutputInterface $output)
    {
        if (extension_loaded('xdebug')) {
            ini_set('xdebug.max_nesting_level', 2000);
        }

        $apiClient = apiClient::factory();

        $serversList = $apiClient->call('server', 'getList')['data'];

        $menuMain = new menu();
        foreach ($serversList as $server) {
            $menuMain->addItem(new menuItem($server['_id'], $server,$server['host']));
        }

        $menuMain->addControlItem(new itemFilter(terminal::KEY_F9, 'F9', 'FilterByName'));
        $menuMain->addControlItem(new menuControlItem(terminal::KEY_ENTER, 'Enter', 'ProcessList', function (menu $parentMenu, $menuControlItem) use ($apiClient) {
            $menuItem = $parentMenu->getCurrentItem();
            $serverId = $menuItem->getValue()['_id'];

            $processList = $apiClient->call('process', 'getByServerId', ['server_id' => $serverId])['data'];

            $parentMenu->close();
            $menu = new menu();
            foreach ($processList as $process) {
                $menu->addItem(new menuItem($process['_id'], $process, $process['name']));
            }

            $menu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Back:', function (menu $parentMenu, $menuControlItem) use ($parentMenu) {
                $parentMenu->open();
            }));
            $menu->addControlItem(new menuControlItem(terminal::KEY_F7, 'F7', 'start', function (menu $parentMenu, $menuControlItem) use ($apiClient) {
                $menuItems = $parentMenu->getMenuItems();
                foreach ($menuItems as  $item) {
                    if (!$item->isSelected()) {
                        continue;
                    }
                    echo "starting {$item->getValue()['name']}";
                    sleep(1);
                }
            }));
            $menu->addControlItem(new menuControlItem(terminal::KEY_F8, 'F8', 'restart', function (menu $parentMenu, $menuControlItem) {
                $menuItems = $parentMenu->getMenuItems();
                foreach ($menuItems as  $item) {
                    if (!$item->isSelected()) {
                        continue;
                    }
                    echo "restarting {$item->getValue()['name']}";
                    sleep(1);
                }
            }));
            $menu->addControlItem(new menuControlItem(terminal::KEY_F9, 'F9', 'stop', function (menu $parentMenu, $menuControlItem) {
                $menuItems = $parentMenu->getMenuItems();
                foreach ($menuItems as  $item) {
                    if (!$item->isSelected()) {
                        continue;
                    }
                    echo "stopping {$item->getValue()['name']}";
                    sleep(1);
                }
            }));

            $menu->open();
        }));

        $menuMain->open();

    }
}