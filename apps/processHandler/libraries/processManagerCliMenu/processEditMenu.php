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
            unset($process['state']);
            $doNotDisplay = [
                '_id' => '_id',
                'last_update' => 'last_update',
                'server' => 'server',
                'state' => 'state'
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

            //process array field edit menu
            if (is_array($itemValue)) {
                $parentMenu->close();
                $menu = new menu();
                foreach ($itemValue as $key => $value) {
                    $menu->addItem(new menuItem($key, $value, $value));
                }
                $menu->addControlItem(new menuControlItem(terminal::KEY_F4, 'F4', 'remove:', function (menu $currentMenu, $menuControlItem) use ($parentMenu) {
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
                $menu->addControlItem(new menuControlItem(terminal::KEY_F5, 'F5', 'add:', function (menu $currentMenu, $menuControlItem) use ($parentMenu) {
                    $input = trim(readline("/"));
                    if (empty($input)) {
                        echo "Sorry, empty string! \n";
                        sleep(3);
                    }
                    $currentMenu->addItem(new menuItem($input, $input, $input));
                }));
                $menu->addControlItem(new menuControlItem(terminal::KEY_F6, 'F6', 'Done:', function (menu $currentMenu, $menuControlItem) use ($parentMenu, $item) {
                    //setting new array values
                    $newItemValue = [];
                    $arrayEditMenuItems = $currentMenu->getMenuItems();
                    foreach ($arrayEditMenuItems as $arrayEditMenuItem) {
                        if (!$arrayEditMenuItem->isEnabled()) {
                            continue;
                        }
                        $newItemValue[] = $arrayEditMenuItem->getValue();
                    }
                    $item->setValue($newItemValue);
                    $item->setTitle("{$item->getKey()}:" . json_encode($newItemValue, 448));

                    //close current menu and open parent
                    $currentMenu->close();
                    $parentMenu->open();
                }));

                $menu->open();
            } else {
                //process string field edit
                $input = trim(readline("/"));
                if (empty($input)) {
                    echo "Sorry, empty string! \n";
                    sleep(3);
                    return;
                }
                if ($item->getKey() === 'instances') {
                    $input = (int) $input;
                }
                $item->setValue($input);
                $item->setTitle("{$item->getKey()}:{$input}");
            }

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
                if ($result['status'] && isset($result['data']['upserted'])) {
                    $process['_id'] = $result['data']['upserted'];
                    $processListMenu->addItem(new menuItem($process['_id'], $process, $process['name']));
                }
            }

            var_dump($result);sleep(5);
            $processEditMenu->close();
            $processListMenu->open();
        }));


        $menu->open();
    }

}