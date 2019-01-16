<?php

namespace mpcmf\apps\processHandler\commands\processHandler;

use Codedungeon\PHPCliColors\Color;
use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\itemFilter;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\cliMenu\pageDownControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\pageUpControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\selectAllControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;
use mpcmf\apps\processHandler\libraries\processManager\process;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\processEditControlItem;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\processManagementControlItem;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\processNewControllerItem;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\sortByTitleControlItem;
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
        $menuMain->addControlItem(new selectAllControlItem(terminal::KEY_F6, 'F6', 'SelectAll'));
        $menuMain->addControlItem(new sortByTitleControlItem(terminal::KEY_F10, 'F10', 'Sorted'));
        $menuMain->addControlItem(new processNewControllerItem(terminal::KEY_F12, 'F12', 'New process'));
        $menuMain->addControlItem(new pageDownControlItem());
        $menuMain->addControlItem(new pageUpControlItem());

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
                $processIds = [];
                foreach ($processList as $process) {
                    $processIds[$process['_id']] = $process['_id'];
                    $timeAfterLastUpdate = time() - $process['last_update'];
                    $state = $process['state'];
                    $stateColor = Color::GREEN;

                    if ($state === 'stop' || $state === 'stopped') {
                        $stateColor = Color::RED;
                    }

                    if ($timeAfterLastUpdate > process::TIMEOUT_SECONDS) {
                        $stateColor = Color::YELLOW;
                        $state = "timeout {$timeAfterLastUpdate} seconds";
                    }

                    $state = $stateColor . " {$state}" . Color::RESET;
                    $title = helper::padding($process['name'], helper::padding($state, $serversList[$process['server']]['host'], 40), 80);

                    if ($update) {
                        if (isset($menuItemsByKey[$process['_id']])) {
                            $menuItemsByKey[$process['_id']]->setValue($process);
                            $menuItemsByKey[$process['_id']]->setTitle($title);
                        }
                    } else {
                        $menu->addItem(new menuItem($process['_id'], $process, $title));
                    }
                }
                $menuItems = $menu->getMenuItems();
                foreach ($menuItems as $key => $menuItem) {
                    if (!isset($processIds[$menuItem->getKey()])) {
                        unset($menuItems[$key]);
                    }
                }
                $menuItems = array_values($menuItems);
                $menu->setMenuItems($menuItems);
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
            $menu->addControlItem(new sortByTitleControlItem(terminal::KEY_F10, 'F10', 'Sorted'));
            $menu->addControlItem(new processManagementControlItem(terminal::KEY_DELETE, 'DEL', 'delete', 'delete', 'stopped'));
            $menu->addControlItem(new pageDownControlItem());
            $menu->addControlItem(new pageUpControlItem());

            //process edit menul
            $menu->addControlItem(new processEditControlItem(terminal::KEY_ENTER, 'Enter', 'Edit'));

            $menu->open();
        }));

        $menuMain->open();

    }
}