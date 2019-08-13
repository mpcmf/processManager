<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use Codedungeon\PHPCliColors\Color;
use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\cliMenu\controlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menuFactory;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;
use mpcmf\apps\processHandler\libraries\communication\operationResult;
use mpcmf\apps\processHandler\libraries\communication\prompt;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class copyProcessControlItem  extends controlItem
{

    public function execute(menu $processListMenu)
    {
        $processes = $this->getProcesses($processListMenu);
        if (empty($processes)) {
            return;
        }

        $menu = menuFactory::getMenu();
        foreach ($this->getServerMenuItems() as $key => $value) {
            $menu->addItem(new menuItem($key, $value, $key));
        }

        $menu->addControlItem(new menuControlItem(terminal::KEY_LEFT, '<--', 'Back:', function (menu $currentMenu) {
            $currentMenu->close();
        }));

        $menu->addControlItem(new menuControlItem(terminal::KEY_ENTER, 'Enter', 'Select', function (menu $menu) use ($processes) {
            $currentItem = $menu->getCurrentItem();

            $serverId = $currentItem->getValue();
            $serverHost = $currentItem->getKey();

            $message = "Do you want to copy processes: "
                . Color::GREEN . "\n\t- " . implode(", \n\t- ", array_column($processes, 'name')) . Color::RESET
                . "\n to" . Color::GREEN . "\n\t- {$serverHost}" . Color::RESET . ' ? ';

            $prompt = new prompt($menu);
            $agree = $prompt->getAgreement($message);

            if ($agree) {
                $result = apiClient::factory()->call('process', 'copy', ['ids' => array_column($processes, '_id'), 'server' => $serverId]);
                if ($result['status'] === false) {
                    $errors = isset($result['data']['errors']) ? $result['data']['errors'] : [];
                    operationResult::notify($result['status'], $errors);
                }
            }

            $menu->close();
        }));

        $menu->open();
    }

    private function getProcesses(menu $processListMenu)
    {
        $processes = [];

        $processMenuItems = $processListMenu->getMenuItems();
        if (empty($processMenuItems)) {
            return $processes;
        }

        /** @var menuItem $item */
        foreach ($processMenuItems as $item) {
            if (!$item->isSelected()) {
                continue;
            }

            $processes[] = $item->export();
        }

        if (empty($processes)) {
            $currentProcessItem = $processListMenu->getCurrentItem();
            $processes[] = $currentProcessItem->export();
        }

        return $processes;
    }

    private function getServerMenuItems()
    {
        $serverMenuItems = [];
        $serversList = apiClient::factory()->call('server', 'getList')['data'];
        foreach ($serversList as $serverData) {
            $serverMenuItems[$serverData['host']] = $serverData['_id'];
        }

        return $serverMenuItems;
    }
}