<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;

class pageDownControlItem
    extends controlItem
{
    protected $actionOnSelectedItem;


    public function __construct()
    {
        parent::__construct(terminal::KEY_PAGE_DOWN, '', '');
    }

    public function execute(menu $menu)
    {
        $menu->cursorDown(5);
    }
}