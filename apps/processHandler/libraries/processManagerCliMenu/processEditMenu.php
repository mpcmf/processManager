<?php


namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;

class processEditMenu
{

    public static function createMenu(menuItem $processItem, menu $processListMenu)
    {
        $apiClient = apiClient::factory();
        $process = $processItem->getValue();
        if (!empty($process['_id'])) {
            unset($process['last_update']);
            $doNotDisplay = [
                '_id' => '_id',
                'last_update' => 'last_update'
            ];
        }
        $menu = new menu();

        $menu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Back:', function (menu $currentMenu, $menuControlItem) use ($processListMenu) {
            $currentMenu->close();
            $processListMenu->open();
        }));

        foreach ($process as $fieldName => $fieldValue) {
            if (isset($doNotDisplay[$fieldName])) {
                continue;
            }
            $titleValue = is_array($fieldValue) ? json_encode($fieldValue, 448) : $fieldValue;
            $menu->addItem(new menuItem($fieldName, $fieldValue, "{$fieldName}:{$titleValue}"));
        }
        $menu->addControlItem(new menuControlItem(terminal::KEY_ENTER, 'Enter', 'Edit', function (menu $parentMenu, $menuControlItem)  {
            $item = $parentMenu->getCurrentItem();
            $itemValue = $item->getValue();

            $input = null;
            //process array field edit menu
            if (is_array($itemValue)) {
                $arrayEditmenu = new menu();
                foreach ($itemValue as $key => $value) {
                    $arrayEditmenu->addItem(new menuItem($key, $value, $value));
                }

                $arrayEditmenu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Done:', function (menu $currentMenu, $menuControlItem) use ($parentMenu, &$input) {
                    //setting new array values
                    $newItemValue = [];
                    $arrayEditMenuItems = $currentMenu->getMenuItems();
                    foreach ($arrayEditMenuItems as $arrayEditMenuItem) {
                        if (!$arrayEditMenuItem->isEnabled()) {
                            continue;
                        }
                        $newItemValue[] = $arrayEditMenuItem->getValue();
                    }
                    $input = $newItemValue;

                    //close current menu and open parent
                    $currentMenu->close();
                }));

                $arrayEditmenu->addControlItem(new menuControlItem(terminal::KEY_DELETE, 'Del', 'remove:', function (menu $currentMenu, $menuControlItem) use ($parentMenu) {
                    $items = $currentMenu->getMenuItems();
                    $hasSelected = false;
                    foreach ($items as $item) {
                        if ($item->isSelected()) {
                            $item->disable();
                            $hasSelected = true;
                        }
                    }
                    if (!$hasSelected) {
                        $currentMenu->getCurrentItem()->disable();
                    }
                }));
                $arrayEditmenu->addControlItem(new menuControlItem(terminal::KEY_INSERT, 'Ins', 'add:', function (menu $currentMenu, $menuControlItem) use ($parentMenu) {
                    $input = trim(readline("/"));
                    if (empty($input)) {
                        echo "Sorry, empty string! \n";
                        sleep(3);
                    }
                    $currentMenu->addItem(new menuItem($input, $input, $input));
                }));

                $arrayEditmenu->open();
            } elseif ($item->getKey() === 'mode' || $item->getKey() === 'state') {
                $multipleSelectMenu = new menu();
                if ($item->getKey() === 'mode') {
                    $toSelect = [
                        'one_run',
                        'repeatable'
                    ];
                } else {
                    $toSelect = [
                        'run',
                        'stop',
                        'restart'
                    ];
                }

                foreach ($toSelect as $key => $value) {
                    $multipleSelectMenu->addItem(new menuItem($key, $value, $value));
                }

                $multipleSelectMenu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Back:', function (menu $currentMenu, $menuControlItem) use ($item, &$input) {
                    $input = $item->getValue();
                    $currentMenu->close();
                }));

                $multipleSelectMenu->addControlItem(new menuControlItem(terminal::KEY_ENTER, 'Enter', 'Select', function (menu $currentMenu, $menuControlItem) use (&$input)  {
                    $input = $currentMenu->getCurrentItem()->getValue();
                    $currentMenu->close();
                }));
                $multipleSelectMenu->open();

            } else {
                $input = trim(readline("/"));
            }

            if (empty($input)) {
                echo "Sorry, empty string! \n";
                sleep(3);
                return;
            }
            if ($item->getKey() === 'instances') {
                $input = (int) $input;
            }

            $titleValue = is_array($input) ? json_encode($input, 448) : $input;
            $item->setValue($input);
            $item->setTitle("{$item->getKey()}:{$titleValue}");

        }));

        $menu->addControlItem(new menuControlItem(terminal::KEY_F6, 'F6', 'Save', function (menu $processEditMenu, $menuControlItem) use ($process, $apiClient, $processListMenu)  {
            $items = $processEditMenu->getMenuItems();
            foreach ($items as $item) {
                $process[$item->getKey()] = $item->getValue();
            }

            if (!empty($process['_id'])) {
                $id = $process['_id'];
                unset($process['_id']);
                $result = $apiClient->call('process', 'update', ['ids' => [$id], 'fields_to_update' => $process]);
            } else {
                $result = $apiClient->call('process', 'add', ['object' => $process]);
            }

            var_dump($result);sleep(3);
            $processEditMenu->close();
            $processListMenu->open();
        }));


        $menu->open();
    }

}