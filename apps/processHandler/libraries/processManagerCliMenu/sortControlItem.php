<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\menu;

class sortControlItem
    extends controlItem
{
    public function execute(menu $menu)
    {
        $menu->sort();
    }
}