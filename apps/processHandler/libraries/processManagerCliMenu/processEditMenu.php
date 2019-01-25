<?php


namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\cliMenu\sorting;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;

class processEditMenu
{
    public static function createMenu(menuItem $processItem, menu $processListMenu)
    {
        $apiClient = apiClient::factory();
        $menu = new menu(new sorting());
        $menu->setOnRefresh(function () use ($processItem, $menu) {
            $menu->clean();
            $process = $processItem->getValue();
            if (!empty($process['_id'])) {
                unset($process['last_update']);
                $doNotDisplay = [
                    '_id' => '_id',
                    'last_update' => 'last_update'
                ];
            }
            foreach ($process as $fieldName => $fieldValue) {
                if (isset($doNotDisplay[$fieldName])) {
                    continue;
                }
                $titleValue = is_array($fieldValue) ? json_encode($fieldValue, 448) : $fieldValue;
                $menu->addItem(new menuItem($fieldName, $fieldValue, "{$fieldName}:{$titleValue}"));
            }
        });
        $menu->refresh();

        $menu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Back:', function (menu $currentMenu, $menuControlItem) use ($processListMenu) {
            $currentMenu->close();
            $processListMenu->refresh();
            $processListMenu->open();
        }));

        $menu->addControlItem(new menuControlItem(terminal::KEY_ENTER, 'Enter', 'Edit', function (menu $parentMenu, $menuControlItem)  {
            $item = $parentMenu->getCurrentItem();
            $itemValue = $item->getValue();

            $input = null;
            //process array field edit menu
            if (is_array($itemValue)) {
                $arrayEditmenu = new menu(new sorting());
                foreach ($itemValue as $key => $value) {
                    $arrayEditmenu->addItem(new menuItem($key, $value, $value));
                }

                $arrayEditmenu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Done:', function (menu $currentMenu, $menuControlItem) use ($parentMenu, &$input) {
                    //setting new array values
                    $newItemValue = [];
                    $arrayEditMenuItems = $currentMenu->getMenuItems();
                    foreach ($arrayEditMenuItems as $arrayEditMenuItem) {
                        $newItemValue[] = $arrayEditMenuItem->getValue();
                    }
                    $input = $newItemValue;

                    //close current menu and open parent
                    $currentMenu->close();
                }));

                $arrayEditmenu->addControlItem(new menuControlItem(terminal::KEY_DELETE, 'Del', 'remove:', function (menu $currentMenu, $menuControlItem) use ($parentMenu) {
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
                    $items = array_values($items);
                    $currentMenu->setMenuItems($items);
                }));
                $arrayEditmenu->addControlItem(new menuControlItem(terminal::KEY_INSERT, 'Ins', 'add:', function (menu $currentMenu, $menuControlItem) use ($parentMenu) {
                    $currentMenu->reDraw();
                    $input = trim(readline("-->"));
                    if (empty($input)) {
                        echo "Sorry, empty string! \n";
                        sleep(3);
                        return;
                    }
                    $currentMenu->addItem(new menuItem($input, $input, $input));
                }));

                $arrayEditmenu->open();
            } elseif ($item->getKey() === 'mode' || $item->getKey() === 'state') {
                $multipleSelectMenu = new menu(new sorting());
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
                readline_completion_function(function ($input, $index) use ($item) {
                    return [$item->getValue()];
                });
                
                $input = trim(readline("-->"));
            }

            if ($item->getKey() === 'instances') {
                $input = (int) $input;
                if ($input === 0) {
                    echo "Sorry, you can't set 0 instances\n";
                    sleep(3);
                    return;
                }
            }

            $titleValue = is_array($input) ? json_encode($input, 448) : $input;
            $item->setValue($input);
            $item->setTitle("{$item->getKey()}:{$titleValue}");

        }));

        $menu->addControlItem(new menuControlItem(terminal::KEY_F6, 'F6', 'Save', function (menu $processEditMenu, $menuControlItem) use ($processItem, $apiClient, $processListMenu)  {
            $items = $processEditMenu->getMenuItems();
            $process = $processItem->getValue();
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
            $processListMenu->refresh();
            $processListMenu->resetHeaderInfo();
            $processListMenu->open();
        }));


        $menu->open();
    }

}