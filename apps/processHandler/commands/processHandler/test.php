<?php

namespace mpcmf\apps\processHandler\commands\processHandler;

use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\api\client\native;
use mpcmf\apps\processHandler\libraries\cliMenu\itemFilter;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\cliMenu\selectAllControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\processEditControlItem;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\processMenuControlItem;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\processNewControllerItem;
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

        //server list menul
        $menuMain = new menu();
        foreach ($serversList as $server) {
            $menuMain->addItem(new menuItem($server['_id'], $server,$server['host']));
        }
        $menuMain->addControlItem(new itemFilter(terminal::KEY_F4, 'F4', 'FilterByName', 'host'));

        $menuMain->addControlItem(new selectAllControlItem(terminal::KEY_F6, 'F6', 'Select all'));

        //process list menu
        $menuMain->addControlItem(new menuControlItem(terminal::KEY_ENTER, 'Enter', 'ProcessList', function (menu $parentMenu, $menuControlItem) use ($apiClient) {
            $menuItem = $parentMenu->getCurrentItem();
            $serverId = $menuItem->getValue()['_id'];
            $processList = $apiClient->call('process', 'getByServerId', ['server_id' => $serverId])['data'];

            $parentMenu->close();
            $menu = new menu();
            foreach ($processList as $process) {
                $menu->addItem(new menuItem($process['_id'], $process, $process['name']));
            }

            $menu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Back:', function (menu $currentMenu, $menuControlItem) use ($parentMenu) {
                $currentMenu->close();
                $parentMenu->open();
            }));

            $menu->addControlItem(new itemFilter(terminal::KEY_F2, 'F2', 'FilterByCommand', 'command'));
            $menu->addControlItem(new itemFilter(terminal::KEY_F3, 'F3', 'FilterByState', 'state'));
            $menu->addControlItem(new itemFilter(terminal::KEY_F4, 'F4', 'FilterByName', 'name'));
            $menu->addControlItem(new itemFilter(terminal::KEY_F5, 'F5', 'FilterByTag', 'tags'));
            $menu->addControlItem(new selectAllControlItem(terminal::KEY_F6, 'F6', 'Select all'));
            $menu->addControlItem(new processMenuControlItem(terminal::KEY_F7, 'F7', 'start', 'start', 'running'));
            $menu->addControlItem(new processMenuControlItem(terminal::KEY_F8, 'F8', 'restart', 'restart', 'running'));
            $menu->addControlItem(new processMenuControlItem(terminal::KEY_F9, 'F9', 'stop', 'stop', 'stopped'));
            $menu->addControlItem(new processMenuControlItem(terminal::KEY_DELETE, 'DEL', 'delete', 'delete', 'stopped'));

            //process edit menul
            $menu->addControlItem(new processEditControlItem(terminal::KEY_ENTER, 'Enter', 'Edit'));
            $menu->addControlItem(new processNewControllerItem(terminal::KEY_F12, 'F12', 'New process', $serverId));


            $menu->open();
        }));

        $menuMain->open();

    }
}