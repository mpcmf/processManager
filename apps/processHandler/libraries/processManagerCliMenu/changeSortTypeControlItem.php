<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\controlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menuFactory;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\cliMenu\sorting;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;

class changeSortTypeControlItem
    extends controlItem
{
    public function execute(menu $menu)
    {
        $sortMenu = menuFactory::getMenu();
        $fields = array_keys($menu->getMenuItems()[0]->getValue());
        $this->updateHeader($sortMenu, $menu);

        foreach ($fields as $key => $field) {
            $sortMenu->addItem(new menuItem($key, $field, $field));
        }

        $sortMenu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Back:', function (menu $sortMenu) use ($menu) {
            $sortMenu->close();
            $menu->open();
        }));

        $sortMenu->addControlItem(new menuControlItem(terminal::KEY_F2, 'F2','Set default sort', function(menu $sortMenu) use ($menu) {
            $menu->setSortType(null);
            $this->updateHeader($sortMenu, $menu);
        }));

        $sortMenu->addControlItem(new menuControlItem(terminal::KEY_ENTER, 'Enter', 'Select', function (menu $sortMenu) use ($menu) {
            $sortType = $sortMenu->getCurrentItem()->getValue();
            $menu->setSortType($sortType);
            $this->updateHeader($sortMenu, $menu);
        }));

        $sortMenu->open();
    }

    private function updateHeader(menu $sortMenu, menu $menu) {
        $sortMenu->setHeaderInfo("Current sort type: {$menu->getSortType()}");
    }
}