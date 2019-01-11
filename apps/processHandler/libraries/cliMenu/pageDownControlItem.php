<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

class pageDownControlItem
    extends controlItem
{
    protected $actionOnSelectedItem;


    public function __construct()
    {
        $this->keyboardEventNumber = terminal::KEY_PAGE_DOWN;
        $this->buttonName = '';
        $this->title = '';
    }

    public function execute(menu $menu)
    {
        $menu->cursorDown(5);
    }
}