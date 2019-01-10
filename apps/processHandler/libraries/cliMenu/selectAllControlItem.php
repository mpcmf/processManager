<?php


namespace mpcmf\apps\processHandler\libraries\cliMenu;

class selectAllControlItem
    extends controlItem
{
    /**
     * selectAllControlItem constructor.
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
        $this->selectAll($menu);
    }

    public function selectAll(menu $parentMenu)
    {
        static $select = true;
        $items = $parentMenu->getMenuItems();
        foreach ($items as $item) {
            $item->setSelected($select);
        }
        $select = $select ? false : true;
    }
}