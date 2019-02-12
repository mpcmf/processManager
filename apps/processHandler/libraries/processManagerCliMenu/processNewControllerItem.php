<?php

namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\cliMenu\controlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;

class processNewControllerItem
    extends controlItem
{
    /** @var menu $serverItem */
    private $serverItem;

    public function __construct($keyboardEventNumber, $buttonName, $title, menuItem $serverItem)
    {
        parent::__construct($keyboardEventNumber, $buttonName, $title);
        $this->serverItem = $serverItem;
    }

    public function execute(menu $menu)
    {
        $menu->close();
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
            'server' => $this->serverItem->getValue()['_id']
        ], 'new process');

        processEditMenu::createMenu($processItem, $menu);
    }
}