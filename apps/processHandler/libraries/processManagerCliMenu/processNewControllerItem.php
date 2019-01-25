<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\controlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;

class processNewControllerItem
    extends controlItem
{
    public function execute(menu $menu)
    {
        $this->actionOnSelectedItem($menu);
    }

    protected function actionOnSelectedItem (menu $serverListMenu)
    {
        $serverListMenu->close();
        $processItem = new menuItem('', [
            'name' => '',
            'description' => '',
            'state' => 'stopped',
            'mode' => 'repeatable',
            'command' => '',
            'work_dir' => '',
            'tags' => [],
            'std_out' => [],
            'std_error' => [],
            'instances' => 1,
            'server' => $serverListMenu->getCurrentItem()->getValue()['_id']
        ], 'new process');

        processEditMenu::createMenu($processItem, $serverListMenu);
    }
}