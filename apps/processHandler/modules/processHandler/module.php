<?php

namespace mpcmf\modules\processHandler;

use mpcmf\modules\moduleBase\moduleBase;
use mpcmf\system\pattern\singleton;

/**
 * SDS processHandler
 *
 * @author Gregory Ostrovsky <greevex@gmail.com>
 */
class module
    extends moduleBase
{
    use singleton;

    protected function bindAclGroups()
    {

    }

    public function getName()
    {
        return 'Process Handler';
    }

}