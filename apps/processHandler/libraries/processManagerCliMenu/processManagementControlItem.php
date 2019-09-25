<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\api\locker;
use mpcmf\apps\processHandler\libraries\cliMenu\controlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;
use mpcmf\apps\processHandler\libraries\communication\operationResult;
use mpcmf\apps\processHandler\libraries\communication\prompt;
use mpcmf\apps\processHandler\libraries\menuItem\process\processMenuItem;
use mpcmf\apps\processHandler\libraries\menuItem\process\stateMenuItem;

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

        if (!$this->prompt($processListMenu)) {
            return;
        }

        $result = $apiClient->call('process', $this->processMethod, ['ids' => $ids]);

        $success = $result['status'];

        if ($success === false) {
            $errors = isset($result['data']['errors']) ? $result['data']['errors'] : [];
            operationResult::notify($success, $errors);
            return;
        }

        locker::lockWrite($ids);

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
                /** @var processMenuItem $processMenuItem */
                $processMenuItem = $processListMenu->getMenuItemByKey($process['_id']);
                /** @var stateMenuItem $stateMenuItem */
                $stateMenuItem = $processMenuItem->getValue()['state'];
                $stateMenuItem->setValue($process['state']);
                $processMenuItem->setTitle($processMenuItem->formTitle());
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
        $prompt = new prompt($menu);
        switch ($this->keyboardEventNumber) {
            case terminal::KEY_DELETE:
                $message = 'Do you want delete?';
                return $prompt->getAgreement($message);
                break;
            default:
                return true;
                break;
        }
    }
}