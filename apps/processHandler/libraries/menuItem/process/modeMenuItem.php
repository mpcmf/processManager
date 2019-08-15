<?php

namespace mpcmf\apps\processHandler\libraries\menuItem\process;

use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\menuItem\selectableEditMenuItem;
use mpcmf\modules\processHandler\mappers\processMapper;

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
            processMapper::MODE__ONE_RUN => processMapper::MODE__ONE_RUN,
            processMapper::MODE__REPEATABLE => processMapper::MODE__REPEATABLE,
            processMapper::MODE__PERIODIC => processMapper::MODE__PERIODIC
        ];
    }
}
