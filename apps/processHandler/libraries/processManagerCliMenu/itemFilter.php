<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use Codedungeon\PHPCliColors\Color;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;

class itemFilter
    extends controlItem
{
    protected $filterBy;

    /**
     * itemFilter constructor.
     *
     * @param $keyboardEventNumber
     * @param $buttonName
     * @param $title
     * @param $filterBy
     */
    public function __construct($keyboardEventNumber, $buttonName, $title, $filterBy = null)
    {
        parent::__construct($keyboardEventNumber, $buttonName, $title);
        $this->filterBy = $filterBy;
    }

    protected function filter(menu $menu, $controlItem)
    {
        $menu->reDraw();
        $input = trim(readline("-->"));

        $items = $menu->getMenuItemsOrigin();
        $matched = false;
        /** @var menuItem $menuItem */
        foreach ($items as $key => $menuItem) {
            if (!empty($input) && !$this->checkCondition($menuItem, $input)) {
                unset($items[$key]);
                continue;
            }
            $matched = true;
        }
        $items = array_values($items);

        if (!$matched) {
            $items = $menu->getMenuItemsOrigin();
        }
        $menu->setMenuItems($items);

        if (!empty($input)) {
            if (!$matched) {
                $input = Color::RED . $input . Color::RESET;
            }
            $menu->setHeaderInfo("{$controlItem->getTitle()}:[{$input}]");
        } else {
            $menu->resetHeaderInfo();
        }

    }

    protected function checkCondition(menuItem $menuItem, $input)
    {
        $haystack = $this->filterBy ? $menuItem->getValue()[$this->filterBy] : $menuItem->getTitle();

        if (is_array($haystack)) {
            $haystack = json_encode($haystack);
        }
        if (mb_stripos($haystack, $input) !== false) {
            return true;
        }

        return false;
    }

    public function execute(menu $menu)
    {
        $this->filter($menu, $this);
    }

}