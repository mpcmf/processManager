<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menuFactory;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;
use mpcmf\apps\processHandler\libraries\communication\prompt;
use mpcmf\apps\processHandler\libraries\menuItem\arrayEditableMenuItem;
use mpcmf\apps\processHandler\libraries\menuItem\objectEditMenuItem;
use mpcmf\apps\processHandler\libraries\menuItem\process\processMenuItem;
use mpcmf\apps\processHandler\libraries\menuItem\selectableEditMenuItem;
use mpcmf\apps\processHandler\libraries\communication\operationResult;

class processEditMenu
{
    public static function createMenu(menu $processListMenu)
    {
        $processEditMenu = self::createObjectEditMenu($processListMenu);

        $processEditMenu->addControlItem(new menuControlItem(terminal::KEY_F6, 'F6', 'Save', function (menu $processEditMenu) use ($processListMenu)  {
            $process = $processListMenu->getCurrentItem();
            /** @var processMenuItem $process */
            $process->save();

            $processListMenu->getCurrentItem()->setTitle($process->formTitle());

            $processEditMenu->close();
            $processListMenu->refresh();
            $processListMenu->resetHeaderInfo();
            $processListMenu->open();
        }));

        $processEditMenu->open();
    }

    private static function createObjectEditMenu(menu $parentMenu)
    {
        $processEditMenu = menuFactory::getMenu();
        $currentProcessMenuItem = $parentMenu->getCurrentItem();

        $processEditMenu->setOnRefresh(function (menu $processEditMenu) use ($currentProcessMenuItem) {
            $processEditMenu->clean();
            $processEditMenu->setMenuItems($currentProcessMenuItem->getValue());
        });

        $processEditMenu->refresh();

        $processEditMenu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Back:', function (menu $processEditMenu) use ($parentMenu) {
            $currentItem = $parentMenu->getCurrentItem();
            if ($currentItem instanceof processMenuItem) {
                $ok = $currentItem->reload();
                if (!$ok) {
                    operationResult::notify(false, ['Unable to reload process data']);
                }
                $newTitle = $currentItem->formTitle();
            } else {
                $newTitle = helper::formTitle($currentItem->getKey(), $currentItem->export());
            }

            $currentItem->setTitle($newTitle);
            $parentMenu->getCurrentItem()->setTitle($newTitle);
            $processEditMenu->close();
            $parentMenu->refresh();
            $parentMenu->open();
        }));

        $processEditMenu->addControlItem(new menuControlItem(terminal::KEY_ENTER, 'Enter', 'Edit', function (menu $currentMenu)  {
            $item = $currentMenu->getCurrentItem();
            $itemKey = $item->getKey();
            if ($item instanceof selectableEditMenuItem) {
                $menu = self::createSelectableEditMenu($item, $currentMenu);
                $menu->open();
            } elseif ($item instanceof objectEditMenuItem) {
                $menu = self::createObjectEditMenu($currentMenu);
                $menu->open();
            } elseif($item instanceof arrayEditableMenuItem) {
                $menu = self::createArrayEditMenu($currentMenu);
                $menu->open();
            } else {
                $prompt = new prompt($currentMenu);
                $prompt->completion([$item->getValue()]);
                $input = $prompt->getResponse("New {$item->getKey()}: ");

                $item->setValue($input);
                $item->setTitle(helper::formTitle($itemKey, $input));
            }
        }));

        return $processEditMenu;
    }

    private static function createSelectableEditMenu(selectableEditMenuItem $menuItem, menu $parentMenu)
    {
        $cursor = 0;
        $menu = menuFactory::getMenu();
        foreach ($menuItem->getToSelectItems() as $key => $value) {
            $menu->addItem(new menuItem($key, $value, $key));
            if ($value === $menuItem->getValue()) {
                $menu->setCursorPosition($cursor);
            }
            $cursor++;
        }

        $item = $parentMenu->getCurrentItem();
        $handler = function (menu $currentMenu) use ($item) {
            $currentItem = $currentMenu->getCurrentItem();
            $item->setValue($currentItem->getValue());
            $item->setTitle(helper::formTitle($item->getKey(), $currentItem->getKey()));
            $currentMenu->close();
        };

        $menu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Back:', $handler));
        $menu->addControlItem(new menuControlItem(terminal::KEY_ENTER, 'Enter', 'Select', $handler));

        return $menu;
    }

    private static function createArrayEditMenu(menu $parentMenu)
    {
        $currentItem = $parentMenu->getCurrentItem();
        $menu = menuFactory::getMenu();
        $menu->setMenuItems($currentItem->getValue());

        $menu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Done:', function (menu $currentMenu) use ($currentItem) {
            $newValues = [];
            /** @var menuItem $editedItem */
            foreach ($currentMenu->getMenuItems() as $editedItem) {
                $newValues[] = $editedItem->getValue();
            }

            $currentItem->setTitle(helper::formTitle($currentItem->getKey(), $newValues));

            $currentMenu->close();
        }));

        $menu->addControlItem(new menuControlItem(terminal::KEY_DELETE, 'Del', 'remove:', function (menu $currentMenu) use ($currentItem) {
            $menuItems = $currentMenu->getMenuItems();
            $hasSelected = false;
            /** @var menuItem $item */
            foreach ($menuItems as $item) {
                if ($item->isSelected()) {
                    $currentMenu->dropMenuItemByKey($item->getKey());
                    $hasSelected = true;
                }
            }
            if (!$hasSelected) {
                $currentItemKey = $currentMenu->getCurrentItem()->getKey();
                $currentMenu->dropMenuItemByKey($currentItemKey);
            }

            $currentItem->setValue($currentMenu->getMenuItems());
        }));

        $menu->addControlItem(new menuControlItem(terminal::KEY_INSERT, 'Ins', 'add:', function (menu $currentMenu) use ($currentItem) {
            $prompt = new prompt($currentMenu);
            $input = $prompt->getResponse("New {$currentItem->getKey()}: ");
            if (!empty($input)) {
                $new = new menuItem($input, $input, $input);
                $items = $currentItem->getValue();
                $items[] = $new;

                $currentItem->setValue($items);
                $currentMenu->addItem($new);
            }
        }));

        return $menu;
    }
}
