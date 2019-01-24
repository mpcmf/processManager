<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\menu;

class processEditControlItem
    extends controlItem
{
    public function execute(menu $menu)
    {
        $this->actionOnSelectedItem($menu);
    }

    protected function actionOnSelectedItem (menu $processListMenu)
    {
        $processListMenu->close();
        $processItem = $processListMenu->getCurrentItem();
        if (!$processItem) {
            $processListMenu->open();
            return;
        }
        processEditMenu::createMenu($processItem, $processListMenu);
    }
}