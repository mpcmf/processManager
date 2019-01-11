<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

use Codedungeon\PHPCliColors\Color;

class menu
{

    protected $opened = false;
    /**
     * @var menuItem[]
     */
    protected $menuItems = [];

    /**
     * @var menuItem[]
     */
    protected $menuItemsOrigin;

    /**
     * @var controlItem[]
     */
    protected $menuControlItems = [];
    protected $cursor = 0;
    protected $headerInfo = '';
    protected $onRefresh;
    protected $from = 0;

    public function addItem(menuItem $menuItem)
    {
        $this->menuItems[] = $menuItem;
    }

    public function clean()
    {
        $this->menuItems = [];
    }

    public function setOnRefresh(callable $onRefresh)
    {
        $this->onRefresh = $onRefresh;
    }

    public function refresh()
    {
        $action = $this->onRefresh;
        if (is_callable($action)) {
            $action();
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
        foreach ($this->menuItems as $key => $menuItem) {
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
        $menuItemsCount = count($this->menuItems);
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
            $this->menuItemsOrigin = $this->menuItems;
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
                    $this->menuItems[$this->cursor]->toggleSelected();
                    $this->cursorDown();
                    break;
                default:
                    foreach ($this->menuControlItems as $controlItem) {
                        if ($controlItem->getKeyboardEventNumber() == $input) {
                            $controlItem->execute($this);
                        }
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
        if (!isset($this->menuItems[$this->cursor - $positionsCount])) {
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
        if (!isset($this->menuItems[$this->cursor + $positionsCount])) {
            $itemsCount = count($this->menuItems);
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

    /**
     * @return menuItem[]
     */
    public function getMenuItemsOrigin()
    {
        return $this->menuItemsOrigin;
    }

    /**
     * @return menuItem[]
     */
    public function getMenuItems()
    {
        return $this->menuItems;
    }

    /**
     * @param array menuItems
     */
    public function setMenuItems(array $menuItems)
    {
        $this->from = 0;
        $this->cursor = 0;
        $this->menuItems = $menuItems;
    }

    /**
     * @return menuItem|bool
     */
    public function getCurrentItem()
    {
        if (empty($this->menuItems)) {
            return false;
        }
        return $this->menuItems[$this->cursor];
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
}