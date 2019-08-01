<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\controlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\menuItem\processMenuItem;

class processNewControllerItem
    extends controlItem
{
    /** @var menu $serverItem */
    private $serverItem;

    public function __construct($keyboardEventNumber, $buttonName, $title, menuItem $serverItem)
    {
        parent::__construct($keyboardEventNumber, $buttonName, $title);
        $this->serverItem = $serverItem;
    }

    public function execute(menu $menu)
    {
        $menu->close();
        $menu->addItem(new processMenuItem('', [], ''));
        $menu->cursorDown();

        processEditMenu::createMenu($menu);
    }
}