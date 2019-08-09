<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\cliMenu\controlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;
use mpcmf\apps\processHandler\libraries\notifcation\operationResult;

class processManagementControlItem
    extends controlItem
{

    protected $processMethod;
    protected $expectedState;

    /**
     * processMenuControlItem constructor.
     *
     * @param        $keyboardEventNumber
     * @param        $buttonName
     * @param        $title
     * @param string $processMethod
     * @param string $expectedState
     */
    public function __construct($keyboardEventNumber, $buttonName, $title, $processMethod = 'start', $expectedState = 'running')
    {
        parent::__construct($keyboardEventNumber, $buttonName, $title);
        $this->processMethod = $processMethod;
        $this->expectedState = $expectedState;
    }

    public function execute(menu $menu)
    {
        $this->actionOnSelectedItem($menu, $this);
    }

    protected  function actionOnSelectedItem (menu $processListMenu, $menuControlItem)
    {
        $apiClient = apiClient::factory();
        $menuItems = $processListMenu->getMenuItems();
        if (empty($menuItems)) {
            return;
        }
        $ids = [];
        /** @var menuItem $item */
        foreach ($menuItems as $item) {
            if (!$item->isSelected()) {
                continue;
            }
            $value = $item->getValue();
            $ids[] = $value['_id']->getValue();
        }

        if (empty($ids)) {
            $ids[] = $processListMenu->getCurrentItem()->getValue()['_id']->getValue();
        }

        $this->prompt($processListMenu);

        $result = $apiClient->call('process', $this->processMethod, ['ids' => $ids]);

        $success = $result['status'];

        if ($success === false) {
            $errors = isset($result['data']['errors']) ? $result['data']['errors'] : [];
            operationResult::notify($success, $errors);
            return;
        }

        $attempts = 20;
        do {
            $result = $apiClient->call('process', 'getByIds', ['ids' => $ids]);
            $processListMenu->setHeaderInfo('Waiting end of action...');
            $processListMenu->refresh();
            $processListMenu->reDraw();

            $success = $result['status'];
            if ($success === false) {
                $errors = isset($result['data']['errors']) ? $result['data']['errors'] : [];

                operationResult::notify($success, $errors);
                break;
            }

            $processes = $result['data'];
            $processedCount = 0;
            foreach ($processes as $process) {
                if ($process['state'] === $this->expectedState) {
                    $processedCount++;
                }
            }
            if (count($processes) === $processedCount) {
                break;
            }
            sleep(1);
        } while ($attempts--);

        $processListMenu->resetHeaderInfo();
    }

    protected function prompt(menu $menu)
    {
        switch ($this->keyboardEventNumber) {
            case terminal::KEY_DELETE:
                $this->deletePrompt($menu);
                break;
            default:
                break;
        }
    }

    protected function deletePrompt(menu $menu)
    {
        do {
            $menu->reDraw();
            $response = readline('Do you want delete? [yes/no]:');
            if ($response === 'no') {
                return;
            }
        } while ($response !== 'yes');
    }
}