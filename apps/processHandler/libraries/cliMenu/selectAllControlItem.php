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

    /**
     * @param menuItem[]
     */
    public function execute(&$menu)
    {
        $this->selectAll($menu);
    }

    public function selectAll(menu $parentMenu)
    {
        static $select = true;
        $items = $parentMenu->getMenuItems();
        foreach ($items as $item) {
            if (!$item->isEnabled()) {
                continue;
            }
            $item->setSelected($select);
        }
        $select = $select ? false : true;
    }
}