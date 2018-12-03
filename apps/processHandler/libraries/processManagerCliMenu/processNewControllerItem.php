<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\controlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;

class processNewControllerItem
    extends controlItem
{

    protected $serverId;

    /**
     * processNewControllerItem constructor.
     *
     * @param $keyboardEventNumber
     * @param $buttonName
     * @param $title
     * @param $serverId
     */
    public function __construct($keyboardEventNumber, $buttonName, $title, $serverId)
    {
        $this->keyboardEventNumber = $keyboardEventNumber;
        $this->buttonName = $buttonName;
        $this->title = $title;
        $this->serverId = $serverId;
    }

    /**
     * @param menuItem[]
     */
    public function execute(&$menu)
    {
        $this->actionOnSelectedItem($menu);
    }

    protected function actionOnSelectedItem (menu $processListMenu)
    {
        $processListMenu->close();
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
            'server' => $this->serverId
        ], 'new process');

        processEditMenu::createMenu($processItem, $processListMenu);
    }
}