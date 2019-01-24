<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;

class endControlItem
    extends controlItem
{
    protected $actionOnSelectedItem;

    public function __construct()
    {
        parent::__construct(terminal::KEY_END, '', '');
    }

    public function execute(menu $menu)
    {
        $menu->cursorDown(count($menu->getMenuItems()) - $menu->getCursorPosition());
    }
}