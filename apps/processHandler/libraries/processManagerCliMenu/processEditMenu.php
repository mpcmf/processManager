<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\helper as titleHelper;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menuFactory;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;

class processEditMenu
{
    private static $multiSelectItems = [
        'mode' => [
            'one_run' => 'one_run',
            'repeatable' => 'repeatable'
        ],
        'state' => [
            'run' => 'run',
            'stop' => 'stop',
            'restart' => 'restart'
        ],
        'type' => [
            'file' => 'file'
        ],
        'enable' => [
            'true' => true,
            'false' => false
        ]
    ];

    private static $objectItems = [
        'logging' => true
    ];

    private static $arrayItems = [
        'tags' => true,
        'handlers' => true
    ];

    public static function createMenu(menu $processListMenu)
    {
        $processEditMenu = self::createObjectEditMenu($processListMenu);

        $processEditMenu->addControlItem(new menuControlItem(terminal::KEY_F6, 'F6', 'Save', function (menu $processEditMenu) use ($processListMenu)  {

            $process = [];
            /** @var menuItem $menuItem */
            foreach ($processEditMenu->getAllMenuItems() as $menuItem) {
                $key = $menuItem->getKey();
                $value = $menuItem->getValue();
                if (!is_array($value)) {
                    $process[$key] = $value;
                    continue;
                }

                /** @var $item $menuItem */
                foreach ($value as $item) {
                    $process[$key][$item->getKey()] = $item->getValue();
                }

                $process[$key] = json_encode($process[$key]);
            }

            if (!empty($process['_id'])) {
                $id = $process['_id'];
                unset($process['_id']);
                $result = apiClient::factory()->call('process', 'update', ['ids' => [$id], 'fields_to_update' => $process]);
            } else {
                $result = apiClient::factory()->call('process', 'add', ['object' => $process]);
            }

            var_dump($result);sleep(3);
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
            $newValues = [];
            foreach ($processEditMenu->getMenuItems() as $editedItem) {
                $newValues[$editedItem->getKey()] = $editedItem->getValue();
            }

            $currentItem = $parentMenu->getCurrentItem();
            $currentItem->setTitle(helper::formTitle($currentItem->getKey(), $newValues));
            $parentMenu->getCurrentItem()->setTitle(titleHelper::formTitle($currentItem->getKey(), $newValues));
            $processEditMenu->close();
            $parentMenu->refresh();
            $parentMenu->open();
        }));

        $processEditMenu->addControlItem(new menuControlItem(terminal::KEY_ENTER, 'Enter', 'Edit', function (menu $currentMenu)  {
            $item = $currentMenu->getCurrentItem();
            $itemKey = $item->getKey();

            if (isset(self::$multiSelectItems[$itemKey])) {
                $menu = self::createMultiSelectEditMenu(self::$multiSelectItems[$itemKey], $currentMenu);
                $menu->open();
            } elseif (isset(self::$objectItems[$itemKey])) {
                $menu = self::createObjectEditMenu($currentMenu);
                $menu->open();
            } elseif(isset(self::$arrayItems[$itemKey])) {
                $menu = self::createArrayEditMenu($currentMenu);
                $menu->open();
            } else {
                readline_completion_function(function ($input, $index) use ($item) {
                    return [$item->getValue()];
                });

                $input = trim(readline("-->"));
                $item->setValue($input);
                $item->setTitle(titleHelper::formTitle($itemKey, $input));
            }
        }));

        return $processEditMenu;
    }

    private static function createMultiSelectEditMenu(array $toSelect, menu $parentMenu)
    {
        $menu = menuFactory::getMenu();
        $cursor = 0;
        foreach ($toSelect as $key => $value) {
            $menu->addItem(new menuItem($key, $value, $key));
            if ($value === $parentMenu->getCurrentItem()->getValue()) {
                $menu->setCursorPosition($cursor);
            }
            $cursor++;
        }

        $item = $parentMenu->getCurrentItem();
        $handler = function (menu $currentMenu) use ($item)  {
            $newValue = $currentMenu->getCurrentItem()->getValue();
            $item->setValue($newValue);
            $item->setTitle(titleHelper::formTitle($item->getKey(), $newValue));
            $currentMenu->close();
        };

        $menu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Back:', $handler));
        $menu->addControlItem(new menuControlItem(terminal::KEY_ENTER, 'Enter', 'Select', $handler));

        return $menu;
    }

    private static function createArrayEditMenu(menu $parentMenu)
    {
        $menu = menuFactory::getMenu();
        $menu->setMenuItems($parentMenu->getCurrentItem()->getValue());

        $item = $parentMenu->getCurrentItem();
        $menu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Done:', function (menu $currentMenu) use ($item) {
            $newValues = [];
            foreach ($currentMenu->getMenuItems() as $editedItem) {
                $newValues[] = $editedItem->getValue();
            }

            $item->setTitle(titleHelper::formTitle($item->getKey(), $newValues));

            $currentMenu->close();
        }));

        $menu->addControlItem(new menuControlItem(terminal::KEY_DELETE, 'Del', 'remove:', function (menu $currentMenu) {
            $items = $currentMenu->getMenuItems();
            $hasSelected = false;
            foreach ($items as $key => $item) {
                if ($item->isSelected()) {
                    unset($items[$key]);
                    $hasSelected = true;
                }
            }
            if (!$hasSelected) {
                $currentItem = $currentMenu->getCurrentItem();
                foreach ($items as $key => $item) {
                    if ($item === $currentItem) {
                        unset($items[$key]);
                    }
                }
            }

            $currentMenu->setMenuItems(array_values($items));
        }));

        $menu->addControlItem(new menuControlItem(terminal::KEY_INSERT, 'Ins', 'add:', function (menu $currentMenu) {
            $currentMenu->reDraw();
            $input = trim(readline("-->"));
            if (empty($input)) {
                echo "Sorry, empty string! \n";
                sleep(3);
                return;
            }
            $currentMenu->addItem(new menuItem($input, $input, $input));
        }));

        return $menu;
    }

}