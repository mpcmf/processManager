<?php
namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\cliMenu\controlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;

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
        foreach ($menuItems as $item) {
            if (!$item->isSelected()) {
                continue;
            }
            $ids[] = $item->getValue()['_id'];
        }
        if (empty($ids)) {
            $ids[] = $processListMenu->getCurrentItem()->getValue()['_id'];
        }
        if ($this->keyboardEventNumber === terminal::KEY_DELETE) {
            do {
                $processListMenu->reDraw();
                $deletePrompt = readline('Do you want delete? [yes/no]:');
                if ($deletePrompt === 'no') {
                    return;
                }
            } while ($deletePrompt !== 'yes');
        }
        $result = $apiClient->call('process', $this->processMethod, ['ids' => $ids]);

        if (!$result['status']) {
            echo json_encode($result, 448);
            sleep(5);

            return;
        }


        $attempts = 20;
        do {
            $result = $apiClient->call('process', 'getByIds', ['ids' => $ids]);
            $processListMenu->setHeaderInfo('Waiting end of action...');
            $processListMenu->refresh();
            $processListMenu->reDraw();
            if (!$result['status']) {
                echo json_encode($result, 448);
                sleep(5);
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
}