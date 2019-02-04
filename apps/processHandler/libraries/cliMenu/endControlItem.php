<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

class endControlItem
    extends controlItem
{
    protected $actionOnSelectedItem;

    public function __construct()
    {
        $this->keyboardEventNumber = terminal::KEY_END;
        $this->buttonName = '';
        $this->title = '';
    }

    public function execute(menu $menu)
    {
        $menu->cursorDown(count($menu->getMenuItems()) - $menu->getCursor());
    }
}