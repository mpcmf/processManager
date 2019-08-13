<?php

namespace mpcmf\apps\processHandler\commands\processHandler;

use mpcmf\apps\processHandler\libraries\cliMenu\menuFactory;
use mpcmf\apps\processHandler\libraries\menuItem\process\processMenuItem;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\copyMoveProcessControlItem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\itemFilter;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\selectAllControlItem;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\changeSortControlItem;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\processEditControlItem;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\processManagementControlItem;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\processNewControllerItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menuControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;
use mpcmf\system\application\consoleCommandBase;

class processHandlerAdmin
    extends consoleCommandBase
{

    protected function defineArguments()
    {}

    protected function handle(InputInterface $input, OutputInterface $output)
    {
        if (extension_loaded('xdebug')) {
            ini_set('xdebug.max_nesting_level', 2000);
        }

        $serverListMenu = menuFactory::getMenu();
        $serverMenuItems = $this->getServerMenuItems();
        $serverListMenu->setMenuItems($serverMenuItems);

        $serverListMenu->addControlItem(new itemFilter(terminal::KEY_F4, 'F4', 'FilterByName', 'host'));
        $serverListMenu->addControlItem(new selectAllControlItem(terminal::KEY_F6, 'F6', 'SelectAll'));
        $serverListMenu->addControlItem(new menuControlItem(terminal::KEY_F10, 'F10', 'Sorted', function (menu $menu) { $menu->sort(); }));
        $serverListMenu->addControlItem(new changeSortControlItem(terminal::KEY_F12, 'F12', 'Change sort'));

        $serverListMenu->addControlItem(new menuControlItem(terminal::KEY_ENTER, 'Enter', 'ProcessList', function (menu $serverListMenu) {
            $serverMenuItems = $serverListMenu->getMenuItems();
            if (empty($serverMenuItems)) {
                return;
            }

            $selectedServerMenuItems = [];
            foreach ($serverMenuItems as $serverMenuItem) {
                if ($serverMenuItem->isSelected()) {
                    $selectedServerMenuItems[$serverMenuItem->getKey()] = $serverMenuItem;
                }
            }

            if (empty($selectedServerMenuItems)) {
                $currentServerMenu = $serverListMenu->getCurrentItem();
                $selectedServerMenuItems[$currentServerMenu->getKey()] = $currentServerMenu;
            }

            $processListMenu = menuFactory::getMenu();
            $processListMenu->setOnRefresh(function (menu $processListMenu) use ($selectedServerMenuItems) {
                $currentCursor = $processListMenu->getCursorPosition();
                $processMenuItems = $this->getProcessMenuItems($selectedServerMenuItems);
                $processListMenu->setMenuItems($processMenuItems);
                $processListMenu->setCursorPosition($currentCursor);
            });

            $processListMenu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Back:', function (menu $processListMenu) use ($serverListMenu) {
                $processListMenu->close();
                $serverListMenu->open();
            }));

            $processListMenu->addControlItem(new itemFilter(terminal::KEY_F2, 'F2', 'FilterByCommand', 'command'));
            $processListMenu->addControlItem(new itemFilter(terminal::KEY_F3, 'F3', 'FilterByState', 'state'));
            $processListMenu->addControlItem(new itemFilter(terminal::KEY_F4, 'F4', 'FilterByName', 'name'));
            $processListMenu->addControlItem(new itemFilter(terminal::KEY_F5, 'F5', 'FilterByTag', 'tags'));
            $processListMenu->addControlItem(new selectAllControlItem(terminal::KEY_F6, 'F6', 'SelectAll'));
            $processListMenu->addControlItem(new processManagementControlItem(terminal::KEY_F7, 'F7', 'Start', 'start', 'running'));
            $processListMenu->addControlItem(new processManagementControlItem(terminal::KEY_F8, 'F8', 'Restart', 'restart', 'running'));
            $processListMenu->addControlItem(new processManagementControlItem(terminal::KEY_F9, 'F9', 'Stop', 'stop', 'stopped'));
            $processListMenu->addControlItem(new menuControlItem(terminal::KEY_F10, 'F10', 'Sorted', function (menu $menu) { $menu->sort(); }));
            $processListMenu->addControlItem(new changeSortControlItem(terminal::KEY_F12, 'F12', 'Change sort'));
            $processListMenu->addControlItem(new copyMoveProcessControlItem(terminal::KEY_STAR, '*', 'Copy', copyMoveProcessControlItem::ACTION_COPY));
            $processListMenu->addControlItem(new copyMoveProcessControlItem(terminal::KEY_QUESTION, '!', 'Move', copyMoveProcessControlItem::ACTION_MOVE));
            $processListMenu->addControlItem(new processManagementControlItem(terminal::KEY_DELETE, 'Del', 'Delete', 'delete', 'stopped'));
            $processListMenu->addControlItem(new processNewControllerItem(terminal::KEY_INSERT, 'Insert', 'New process', $serverListMenu->getCurrentItem()));
            $processListMenu->addControlItem(new processEditControlItem(terminal::KEY_ENTER, 'Enter', 'Edit'));

            $serverListMenu->close();
            $processListMenu->refresh();
            $processListMenu->open();
        }));

        $serverListMenu->sort();
        $serverListMenu->open();
    }

    private function getProcessMenuItems(array $serverMenuItems)
    {
        $serversMenuByKeys = [];
        /** @var menuItem $menuItem */
        foreach ($serverMenuItems as $menuItem) {
            $serversMenuByKeys[$menuItem->getKey()] = $menuItem;
        }

        $processMenuItems = [];
        $processList = apiClient::factory()->call('process', 'getByServerIds', ['server_ids' => array_keys($serversMenuByKeys)])['data'];
        foreach ($processList as $processData) {
            $processData['server'] = $serversMenuByKeys[$processData['server']]->export();
            $processMenuItems[] = new processMenuItem($processData);
        }

        return $processMenuItems;
    }

    private function getServerMenuItems()
    {
        $serverMenuItems = [];
        $serversList = apiClient::factory()->call('server', 'getList')['data'];
        foreach ($serversList as $serverData) {
            $serverMenuItems[] = new menuItem($serverData['_id'], $serverData, $serverData['host']);
        }

        return $serverMenuItems;
    }
}