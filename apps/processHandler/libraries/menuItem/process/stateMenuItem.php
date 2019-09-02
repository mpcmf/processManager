<?php

namespace mpcmf\apps\processHandler\libraries\menuItem\process;

use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\menuItem\selectableEditMenuItem;
use mpcmf\apps\processHandler\libraries\processManager\processHandler;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class stateMenuItem extends menuItem implements selectableEditMenuItem
{
    public function __construct($value, $isVisible = true)
    {
        parent::__construct('state', $value, helper::formTitle('state', $value), $isVisible);
    }

    public function getToSelectItems()
    {
        return [
            processHandler::STATE__RUN => processHandler::STATE__RUN,
            processHandler::STATE__STOP => processHandler::STATE__STOP,
            processHandler::STATE__RESTART => processHandler::STATE__RESTART,
        ];
    }
}
