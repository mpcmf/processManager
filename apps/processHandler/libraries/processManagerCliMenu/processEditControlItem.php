<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\controlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;

class processEditControlItem
    extends controlItem
{

    /**
     * processEditControlItem constructor.
     *
     * @param $keyboardEventNumber
     * @param $buttonName
     * @param $title
     */
    public function __construct($keyboardEventNumber, $buttonName, $title)
    {
        $this->keyboardEventNumber = $keyboardEventNumber;
        $this->buttonName = $buttonName;
        $this->title = $title;
    }

    public function execute(menu $menu)
    {
        $this->actionOnSelectedItem($menu);
    }

    protected function actionOnSelectedItem (menu $processListMenu)
    {
        $processListMenu->close();
        $processItem = $processListMenu->getCurrentItem();
        if (!$processItem) {
            $processListMenu->open();
            return;
        }
        processEditMenu::createMenu($processItem, $processListMenu);
    }
}