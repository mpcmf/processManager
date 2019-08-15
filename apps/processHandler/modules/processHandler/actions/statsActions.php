<?php

namespace mpcmf\modules\processHandler\actions;

use mpcmf\modules\moduleBase\actions\actionsBase;
use mpcmf\modules\moduleBase\exceptions\actionException;
use mpcmf\system\pattern\singleton;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class statsActions
    extends actionsBase
{

    use singleton;

    /**
     * Set options inside this method
     *
     * @return mixed
     */
    public function setOptions()
    {}

    /**
     * Bind some custom actions
     *
     * @return mixed
     *
     * @throws actionException
     */
    public function bind()
    {}
}