<?php

namespace mpcmf\apps\processHandler\libraries\menuItem\process;

use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\menuItem\selectableEditMenuItem;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class loggingEnableMenuItem extends menuItem implements selectableEditMenuItem
{
    public function __construct($enable = false, $isVisible = true)
    {
        parent::__construct('enabled', $enable,  helper::formTitle('enabled', $enable), $isVisible);
    }

    public function getToSelectItems()
    {
        return [
            'true' => true,
            'false' => false
        ];
    }
}
