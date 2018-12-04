<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\controlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;

class processNewControllerItem
    extends controlItem
{

    /**
     * processNewControllerItem constructor.
     *
     * @param $keyboardEventNumber
     * @param $buttonName
     * @param $title
     */
    public function __construct($keyboardEventNumber, $buttonName, $title)
    {
        $this->keyboardEventNumber = $keyboardEventNumber;
        $this->buttonName = $buttonName;
        $this->title = $title;
    }

    /**
     * @param menuItem[]
     */
    public function execute(&$menu)
    {
        $this->actionOnSelectedItem($menu);
    }

    protected function actionOnSelectedItem (menu $serverListMenu)
    {
        $serverListMenu->close();
        $processItem = new menuItem('', [
            'name' => '',
            'description' => '',
            'state' => 'stopped',
            'mode' => 'repeatable',
            'command' => '',
            'work_dir' => '',
            'tags' => [],
            'std_out' => [],
            'std_error' => [],
            'instances' => 1,
            'server' => $serverListMenu->getCurrentItem()->getValue()['_id']
        ], 'new process');

        processEditMenu::createMenu($processItem, $serverListMenu);
    }
}