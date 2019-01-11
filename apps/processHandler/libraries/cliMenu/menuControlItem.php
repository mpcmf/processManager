<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

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

    public function execute(menu $menu)
    {
        $action = $this->actionOnSelectedItem;
        $action($menu, $this);
    }
}