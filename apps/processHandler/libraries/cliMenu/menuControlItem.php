<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

require_once __DIR__ . '/controlItem.php';

class menuControlItem
    extends controlItem
{
    protected $actionOnSelectedItem;

    /**
     * menuControlItem constructor.
     *
     * @param          $keyboardEventNumber
     * @param          $buttonName
     * @param          $title
     * @param callable $actionOnSelectedItem
     */
    public function __construct($keyboardEventNumber, $buttonName, $title, callable $actionOnSelectedItem)
    {
        $this->keyboardEventNumber = $keyboardEventNumber;
        $this->buttonName = $buttonName;
        $this->title = $title;
        $this->actionOnSelectedItem = $actionOnSelectedItem;
    }

    /**
     * @param menuItem[]
     */
    public function execute(&$menu)
    {
        $action = $this->actionOnSelectedItem;
        $action($menu, $this);
    }
}