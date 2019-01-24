<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

class sorting
{
    private $sortType;

    public function sort(menu $menu) {
        $menuItems = $menu->getMenuItems();

        usort($menuItems, function (menuItem $item1, menuItem $item2) use ($menu) {
            $value1 = $item1->getTitle();
            $value2 = $item2->getTitle();

            if ($this->sortType !== null) {
                $value1 = $item1->getValue()[$this->sortType];
                $value2 = $item2->getValue()[$this->sortType];
            }

            if (is_array($value1) || is_array($value2)) {
                $value1 = count($value1);
                $value2 = count($value2);
            }

            if ($menu->isSorted()) {
                return strcasecmp($value2, $value1);
            }

            return strcasecmp($value1, $value2);
        });

        $menu->setMenuItems($menuItems);
    }

    public function setSortType($sortType)
    {
        $this->sortType = $sortType;
    }

    public function getSortType()
    {
        return $this->sortType;
    }
}