<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

use Codedungeon\PHPCliColors\Color;

require_once __DIR__ . '/controlItem.php';

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
        $this->keyboardEventNumber = $keyboardEventNumber;
        $this->buttonName = $buttonName;
        $this->title = $title;
        $this->filterBy = $filterBy;
    }

    protected function filter(menu $menu, $controlItem)
    {
        $input = trim(readline("/"));

        $items = $menu->getMenuItems();
        $matched = false;
        /** @var menuItem $menuItem */
        foreach ($items as $key => $menuItem) {
            $menuItem->enable();
            if (!$this->checkCondition($menuItem, $input)) {
                $menuItem->disable();
                continue;
            }
            $matched = true;
        }

        if (!$matched) {
            foreach ($items as $key => $menuItem) {
                $menuItem->enable();
            }
        }

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

    /**
     * @param menuItem[]
     */
    public function execute(&$menu)
    {
        $this->filter($menu, $this);
    }

}