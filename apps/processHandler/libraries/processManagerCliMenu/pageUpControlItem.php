<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;

class pageUpControlItem
    extends controlItem
{
    protected $actionOnSelectedItem;


    public function __construct()
    {
        parent::__construct(terminal::KEY_PAGE_UP, '', '');
    }

    public function execute(menu $menu)
    {
        $menu->cursorUp(5);
    }
}