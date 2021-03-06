<?php
namespace mpcmf\modules\processHandler\actions;

use mpcmf\modules\moduleBase\actions\action;
use mpcmf\modules\moduleBase\actions\actionsBase;
use mpcmf\modules\moduleBase\exceptions\actionException;
use mpcmf\system\acl\aclManager;
use mpcmf\system\pattern\singleton;

/**
 * Class processActions
 *
 * Process manager
 *
 *
 * @generated by mpcmf/codeManager
 *
 * @package mpcmf\modules\processHandler\actions;
 * @date 2017-01-20 16:14:08
 *
 * @author Gregory Ostrovsky <greevex@gmail.com>
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class processActions
    extends actionsBase
{

    use singleton;

    /**
     * Set options inside this method
     *
     * @return mixed
     */
    public function setOptions()
    {
        // TODO: Implement setOptions() method.
    }

    /**
     * Bind some custom actions
     *
     * @return mixed
     *
     * @throws actionException
     */
    public function bind()
    {

    }

}