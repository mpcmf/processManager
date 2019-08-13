<?php

namespace mpcmf\apps\processHandler\libraries\menuItem\process;

use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\menuItem\selectableEditMenuItem;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class loggingTypeMenuItem extends menuItem implements selectableEditMenuItem
{
    public function __construct($type = 'file', $isVisible = true)
    {
        parent::__construct('type', $type,  helper::formTitle('type', $type), $isVisible);
    }

    public function getToSelectItems()
    {
        return [
            'file' => 'file',
            //'http' => 'http'
        ];
    }
}
