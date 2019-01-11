<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

class pageUpControlItem
    extends controlItem
{
    protected $actionOnSelectedItem;


    public function __construct()
    {
        $this->keyboardEventNumber = terminal::KEY_PAGE_UP;
        $this->buttonName = '';
        $this->title = '';
    }

    public function execute(menu $menu)
    {
        $menu->cursorUp(5);
    }
}