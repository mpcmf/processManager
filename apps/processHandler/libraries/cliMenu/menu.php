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
     * @var controlItem[]
     */
    protected $menuControlItems = [];

    protected $cursor = 0;

    protected $headerInfo = '';


    public function addItem(menuItem $menuItem)
    {
        $this->menuItems[] = $menuItem;
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

        foreach ($this->menuItems as $key => $menuItem) {
            if (!$menuItem->isEnabled()) {
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
    }

    public function open()
    {
        static $terminal;
        if ($terminal === null) {
            $terminal = new terminal();
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

    protected function cursorUp()
    {
        if ($this->isAllItemsDisabled()) {
            return;
        }
        $itemsCount = count($this->menuItems);
        if ($this->cursor <= 0) {
            $this->cursor = $itemsCount - 1;
        } else {
            $this->cursor--;
        }

        if (!$this->menuItems[$this->cursor]->isEnabled()) {
            $this->cursorUp();
        }
    }

    protected function cursorDown()
    {
        if ($this->isAllItemsDisabled()) {
            return;
        }
        $itemsCount = count($this->menuItems);
        if ($this->cursor >= $itemsCount - 1) {
            $this->cursor = 0;
        } else {
            $this->cursor++;
        }

        if (!$this->menuItems[$this->cursor]->isEnabled()) {
            $this->cursorDown();
        }
    }

    /**
     * @return menuItem[]
     */
    public function getMenuItems()
    {
        return $this->menuItems;
    }

    /**
     * @return menuItem
     */
    public function getCurrentItem()
    {
        return $this->menuItems[$this->cursor];
    }

    protected function isAllItemsDisabled()
    {
        foreach ($this->menuItems as $menuItem) {
            if ($menuItem->isEnabled()) {
                return false;
            }
        }

        return true;
    }

}