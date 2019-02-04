<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

class homeControlItem
    extends controlItem
{
    protected $actionOnSelectedItem;

    public function __construct()
    {
        $this->keyboardEventNumber = terminal::KEY_HOME;
        $this->buttonName = '';
        $this->title = '';
    }

    public function execute(menu $menu)
    {
        $menu->cursorUp($menu->getCursor());
    }
}