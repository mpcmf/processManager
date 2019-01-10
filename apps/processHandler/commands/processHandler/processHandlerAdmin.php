<?php

namespace mpcmf\apps\processHandler\commands\processHandler;

use Codedungeon\PHPCliColors\Color;
use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\itemFilter;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\cliMenu\selectAllControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\processEditControlItem;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\processManagementControlItem;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\processNewControllerItem;
use mpcmf\system\application\consoleCommandBase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class processHandlerAdmin
    extends consoleCommandBase
{

    protected function defineArguments()
    {

    }

    protected function handle(InputInterface $input, OutputInterface $output)
    {
        if (extension_loaded('xdebug')) {
            ini_set('xdebug.max_nesting_level', 2000);
        }

        $apiClient = apiClient::factory();

        $serversList = $apiClient->call('server', 'getList')['data'];

        //server list menul
        $menuMain = new menu();
        foreach ($serversList as $server) {
            $menuMain->addItem(new menuItem($server['_id'], $server,$server['host']));
        }
        $menuMain->addControlItem(new itemFilter(terminal::KEY_F4, 'F4', 'FilterByName', 'host'));

        $menuMain->addControlItem(new menuControlItem(terminal::KEY_F5, 'F5', 'Sorted', function (menu $menuMain, $menuControlItem) {
            $serversItems = $menuMain->getMenuItems();
            usort($serversItems, function ($item1, $item2) {
                $title1 = $item1->getTitle();
                $title2 = $item2->getTitle();
                if ($title1 === $title2) {
                    return 0;
                }
                return ($title1 < $title2) ? -1 : 1;
            });
            $menuMain->setMenuItems($serversItems);
        }));

        $menuMain->addControlItem(new selectAllControlItem(terminal::KEY_F6, 'F6', 'SelectAll'));
        $menuMain->addControlItem(new processNewControllerItem(terminal::KEY_F12, 'F12', 'New process'));

        //process list menu
        $menuMain->addControlItem(new menuControlItem(terminal::KEY_ENTER, 'Enter', 'ProcessList', function (menu $serverListMenu, $menuControlItem) use ($apiClient, $serversList) {
            $serverItems = $serverListMenu->getMenuItems();
            if (empty($serverItems)) {
                return;
            }

            $serverIds = [];
            foreach ($serverItems as $serverItem) {
                if ($serverItem->isSelected()) {
                    $serverIds[] = $serverItem->getValue()['_id'];
                }
            }
            if (empty($serverIds)) {
                $menuItem = $serverListMenu->getCurrentItem();
                $serverIds[] = $menuItem->getValue()['_id'];
            }

            $serverListMenu->close();
            $menu = new menu();
            $menu->setOnRefresh(function () use ($menu, $serversList, $apiClient, $serverIds) {
                $processList = $apiClient->call('process', 'getByServerIds', ['server_ids' => $serverIds, 'limit' => 3000])['data'];
                $menuItems = $menu->getMenuItems();
                $menuItemsByKey = [];
                foreach ($menuItems as $menuItem) {
                    $menuItemsByKey[$menuItem->getKey()] = $menuItem;
                }
                $update = !empty($menuItemsByKey);
                foreach ($processList as $process) {
                    $stateColor = Color::GREEN;
                    if ($process['state'] === 'stop' || $process['state'] === 'stopped') {
                        $stateColor = Color::RED;
                    }
                    $state = $stateColor . " {$process['state']}" . Color::RESET;
                    $title = helper::padding($process['name'], helper::padding($state, $serversList[$process['server']]['host'], 20), 100);
                    if ($update) {
                        if (isset($menuItemsByKey[$process['_id']])) {
                            $menuItemsByKey[$process['_id']]->setValue($process);
                            $menuItemsByKey[$process['_id']]->setTitle($title);
                        }
                    } else {
                        $menu->addItem(new menuItem($process['_id'], $process, $title));
                    }
                }
            });

            $menu->refresh();

            $menu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Back:', function (menu $currentMenu, $menuControlItem) use ($serverListMenu) {
                $currentMenu->close();
                $serverListMenu->open();
            }));

            $menu->addControlItem(new itemFilter(terminal::KEY_F2, 'F2', 'FilterByCommand', 'command'));
            $menu->addControlItem(new itemFilter(terminal::KEY_F3, 'F3', 'FilterByState', 'state'));
            $menu->addControlItem(new itemFilter(terminal::KEY_F4, 'F4', 'FilterByName', 'name'));
            $menu->addControlItem(new itemFilter(terminal::KEY_F5, 'F5', 'FilterByTag', 'tags'));
            $menu->addControlItem(new selectAllControlItem(terminal::KEY_F6, 'F6', 'SelectAll'));
            $menu->addControlItem(new processManagementControlItem(terminal::KEY_F7, 'F7', 'start', 'start', 'running'));
            $menu->addControlItem(new processManagementControlItem(terminal::KEY_F8, 'F8', 'restart', 'restart', 'running'));
            $menu->addControlItem(new processManagementControlItem(terminal::KEY_F9, 'F9', 'stop', 'stop', 'stopped'));
            $menu->addControlItem(new processManagementControlItem(terminal::KEY_DELETE, 'DEL', 'delete', 'delete', 'stopped'));

            //process edit menul
            $menu->addControlItem(new processEditControlItem(terminal::KEY_ENTER, 'Enter', 'Edit'));


            $menu->open();
        }));

        $menuMain->open();

    }
}