<?php

namespace mpcmf\apps\processHandler\libraries\menuItem\process;

use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\menuItem\selectableEditMenuItem;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class modeMenuItem extends menuItem implements selectableEditMenuItem
{
    public function __construct($mode, $isVisible = true)
    {
        parent::__construct('mode', $mode, helper::formTitle('mode', $mode), $isVisible);
    }

    public function getToSelectItems()
    {
        return [
            'one_run' => 'one_run',
            'repeatable' => 'repeatable',
            'periodic' => 'periodic'
        ];
    }
}
