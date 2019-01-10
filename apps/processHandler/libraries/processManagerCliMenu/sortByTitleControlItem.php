<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\controlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;

class sortByTitleControlItem
    extends controlItem
{
    /**
     * menuControlItem constructor.
     *
     * @param          $keyboardEventNumber
     * @param          $buttonName
     * @param          $title
     */
    public function __construct($keyboardEventNumber, $buttonName, $title)
    {
        $this->keyboardEventNumber = $keyboardEventNumber;
        $this->buttonName = $buttonName;
        $this->title = $title;
    }
    public function execute(menu $menu)
    {
        $menuItems = $menu->getMenuItems();
        usort($menuItems, function ($item1, $item2) {
            $title1 = $item1->getTitle();
            $title2 = $item2->getTitle();
            if ($title1 === $title2) {
                return 0;
            }
            return ($title1 < $title2) ? -1 : 1;
        });
        $menu->setMenuItems($menuItems);
    }
}