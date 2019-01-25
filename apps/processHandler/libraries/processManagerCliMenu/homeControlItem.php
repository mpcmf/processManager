<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\controlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;

class homeControlItem
    extends controlItem
{
    protected $actionOnSelectedItem;

    public function __construct()
    {
        parent::__construct(terminal::KEY_HOME, '', '');
    }

    public function execute(menu $menu)
    {
        $menu->cursorUp($menu->getCursorPosition());
    }
}