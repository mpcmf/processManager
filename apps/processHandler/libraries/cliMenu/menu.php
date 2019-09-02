<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

use Codedungeon\PHPCliColors\Color;

class menu
{
    protected $opened = false;

    /** @var menuItem[] */
    protected $menuItemsOrigin;

    /** @var array menuItem[] */
    protected $visibleMenuItems = [];

    /** @var array menuItem[] */
    protected $invisibleMenuItems = [];

    /** @var sorting */
    protected $sorting;

    /* @var filter */
    protected $filter;

    /* @var controlItem[] */
    protected $menuControlItems = [];

    /** @var int */
    protected $cursor = 0;

    /** @var string */
    protected $headerInfo = '';

    /** @var callable */
    protected $onRefresh;

    /** @var int */
    protected $from = 0;

    /** @var bool */
    protected $sorted = false;

    public function __construct(sorting $sorting, filter $filter)
    {
        $this->sorting = $sorting;
        $this->filter = $filter;
    }

    public function addItem(menuItem $menuItem)
    {
        if ($menuItem->isVisible()) {
            $this->visibleMenuItems[] = $menuItem;

            return count($this->visibleMenuItems) - 1;
        }

        $this->invisibleMenuItems[] = $menuItem;

        return count($this->invisibleMenuItems) - 1;
    }

    public function clean()
    {
        $this->invisibleMenuItems = [];
        $this->visibleMenuItems = [];
    }

    public function setOnRefresh(callable $onRefresh)
    {
        $this->onRefresh = $onRefresh;
    }

    public function refresh()
    {
        $action = $this->onRefresh;
        if (is_callable($action)) {
            $action($this);
        }
    }

    public function addControlItem(controlItem $menuControlItem)
    {
        $this->menuControlItems[] = $menuControlItem;
    }

    public function setHeaderInfo($info)
    {
        $this->headerInfo = $info;
    }

    public function resetHeaderInfo()
    {
        $this->headerInfo = '';
    }

    protected function draw()
    {
        foreach ($this->menuControlItems as $controlItem) {
            echo Color::bg_green() . $controlItem->getButtonName() . Color::RESET . $controlItem->getTitle() . ' ';
        }

        echo PHP_EOL . Color::bg_green() . $this->headerInfo . Color::RESET . PHP_EOL;

        $i = 0;
        $to = $this->from + $this->getMaxMenuItemsCount();
        if ($this->from !== 0) {
            echo Color::bg_green() . "more...{$this->from}" . Color::RESET . PHP_EOL;
        } else {
            echo PHP_EOL;
        }
        foreach ($this->visibleMenuItems as $key => $menuItem) {
            $outOfVisibleRange = $i < $this->from || $i >= $to;
            $i++;
            if ($outOfVisibleRange) {
                continue;
            }
            if ($this->cursor === $key) {
                echo Color::bg_cyan() . $menuItem->getTitle() . Color::RESET .  PHP_EOL;
                continue;
            }
            if ($menuItem->isSelected()) {
                echo Color::LIGHT_YELLOW . $menuItem->getTitle(). Color::RESET .  PHP_EOL;
                continue;
            }
            echo $menuItem->getTitle() . PHP_EOL;
        }
        $menuItemsCount = count($this->visibleMenuItems);
        if ($menuItemsCount > $to) {
            $remainedItems = $menuItemsCount - $to;
            echo Color::bg_green() . "more...{$remainedItems}"  . Color::RESET . PHP_EOL;
        }
    }

    public function open()
    {
        static $terminal;
        if ($terminal === null) {
            $terminal = new terminal();
        }
        if ($this->menuItemsOrigin === null) {
            $this->menuItemsOrigin = $this->visibleMenuItems;
        }
        if ($this->opened) {
            $this->reDraw();
            return;
        }
        $this->opened = true;
        while ($this->opened) {
            $this->reDraw();
            $input = $terminal->getInput();
            switch ($input) {
                case terminal::KEY_DOWN :
                    $this->cursorDown();
                    break;
                case terminal::KEY_UP :
                    $this->cursorUp();
                    break;
                case terminal::KEY_SPACE :
                    $this->visibleMenuItems[$this->cursor]->toggleSelected();
                    $this->cursorDown();
                    break;
                case terminal::KEY_PAGE_DOWN :
                    $this->cursorDown(5);
                    break;
                case terminal::KEY_PAGE_UP :
                    $this->cursorUp(5);
                    break;
                case terminal::KEY_HOME :
                    $this->cursorUp($this->getCursorPosition());
                    break;
                case terminal::KEY_END :
                    $this->cursorDown(count($this->getMenuItems()));
                    break;
                default:
                    foreach ($this->menuControlItems as $controlItem) {
                        if ($controlItem->getKeyboardEventNumber() === $input) {
                            $controlItem->execute($this);
                            break 2;
                        }
                    }

                    if ($this->filter !== null) {
                        $this->filter->handleUserInput($this, $input);
                    }

                    break;
            }

            usleep(1000);
        }
    }

    public function reDraw()
    {
        static $terminal;
        if ($terminal === null) {
            $terminal = new terminal();
        }

        $terminal->clean();
        $terminal->moveCursorToTop();
        $this->draw();
    }

    public function close()
    {
        $this->opened = false;
    }

    public function cursorUp($positionsCount = 1)
    {
        if (!isset($this->visibleMenuItems[$this->cursor - $positionsCount])) {
            $this->cursor = 0;
            $this->from = 0;
            return;
        }
        if ($this->cursor - $positionsCount <= $this->from) {
            if ($this->from < $positionsCount) {
                $this->from = 0;
            } else {
                $this->from -= $positionsCount;
            }
        }

        $this->cursor -= $positionsCount;
    }

    public function cursorDown($positionsCount = 1)
    {
        if (!isset($this->visibleMenuItems[$this->cursor + $positionsCount])) {
            $itemsCount = count($this->visibleMenuItems);
            $this->from = $itemsCount < $this->getMaxMenuItemsCount() ? 0 : $itemsCount - $this->getMaxMenuItemsCount();
            $this->cursor = $itemsCount - 1;
            return;
        }
        $this->cursor += $positionsCount;
        $to = $this->from + $this->getMaxMenuItemsCount();
        if ($this->cursor >= $to) {
            $this->from += $positionsCount;
        }
    }

    public function setCursorPosition($position)
    {
        if (!isset($this->visibleMenuItems[$position])) {
            return false;
        }

        $this->cursor = $position;
    }

    /**
     * @return menuItem[]
     */
    public function getMenuItemsOrigin()
    {
        return $this->menuItemsOrigin;
    }

    public function getMenuItems()
    {
        return $this->visibleMenuItems;
    }

    public function getVisibleMenuItems()
    {
        return $this->visibleMenuItems;
    }

    public function getInvisibleMenuItems()
    {
        return $this->invisibleMenuItems;
    }

    /**
     * @return menuItem[]
     */
    public function getAllMenuItems()
    {
        return array_merge($this->visibleMenuItems, $this->invisibleMenuItems);
    }

    /**
     * @param array menuItems
     */
    public function setMenuItems(array $menuItems)
    {
        $this->clean();
        foreach ($menuItems as $menuItem) {
            $this->addItem($menuItem);
        }

        $this->from = 0;
        $this->cursor = 0;
    }

    /**
     * @return menuItem|bool
     */
    public function getCurrentItem()
    {
        if (empty($this->visibleMenuItems)) {
            return false;
        }
        return $this->visibleMenuItems[$this->cursor];
    }

    public function getMenuItemByKey($key)
    {
        foreach ($this->visibleMenuItems as $menuItem) {
            if ($menuItem->getKey() === $key) {
                return $menuItem;
            }
        }

        foreach ($this->invisibleMenuItems as $menuItem) {
            if ($menuItem->getKey() === $key) {
                return $menuItem;
            }
        }

        return false;
    }

    public function dropMenuItemByKey($key)
    {
        foreach ($this->visibleMenuItems as $i => $item) {
            if ($item->getKey() === $key) {
                unset($this->visibleMenuItems[$i]);

                return true;
            }
        }

        foreach ($this->invisibleMenuItems as $i => $item) {
            if ($item->getKey() === $key) {
                unset($this->invisibleMenuItems[$i]);

                return true;
            }
        }

        return false;
    }

    public function getMaxMenuItemsCount()
    {
        static $maxMenuItemsCount;
        if ($maxMenuItemsCount === null) {
            $terminal = new terminal();
            $eolCount = 2;
            $maxMenuItemsCount = $terminal->getHeight() - count($this->menuControlItems) - $eolCount;
        }

        return $maxMenuItemsCount;
    }

    public function isSorted() {
        return $this->sorted;
    }

    /**
     * @return int
     */
    public function getCursorPosition()
    {
        return $this->cursor;
    }

    public function sort()
    {
        $this->sorting->sort($this);
        $this->sorted = !$this->sorted;
        $this->setHeaderInfo($this->sorted ? 'Sorted from lower to higher' : 'Sorted from higher to lower' );
    }

    public function setSortBy($sortBy)
    {
        $this->sorting->setSortBy($sortBy);
    }

    public function getSortBy()
    {
        return $this->sorting->getSortBy() ?: 'title';
    }
}