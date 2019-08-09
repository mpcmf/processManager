<?php

namespace mpcmf\apps\processHandler\libraries\menuItem\process;

use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\menuItem\arrayEditableMenuItem;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class loggingHandlersMenuItem extends menuItem implements arrayEditableMenuItem
{
    public function __construct(array $handlers = [], $isVisible = true)
    {
        $menuItems = [];
        foreach ($handlers as $handler) {
            $menuItems[] = new menuItem($handler, $handler, $handler, $isVisible);
        }

        parent::__construct('handlers', $menuItems,  helper::formTitle('handlers', $handlers), $isVisible);
    }

    public function export()
    {
        $exported = [];
        /** @var menuItem $menuItem */
        foreach ($this->value as $menuItem) {
            $exported[] = $menuItem->export();
        }

        return $exported;
    }
}
