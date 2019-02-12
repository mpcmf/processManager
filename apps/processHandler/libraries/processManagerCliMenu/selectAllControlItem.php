<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\controlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;

class selectAllControlItem
    extends controlItem
{
    public function execute(menu $menu)
    {
        $this->selectAll($menu);
    }

    public function selectAll(menu $parentMenu)
    {
        static $select = true;
        $items = $parentMenu->getMenuItems();
        foreach ($items as $item) {
            $item->setSelected($select);
        }
        $select = $select ? false : true;
    }
}