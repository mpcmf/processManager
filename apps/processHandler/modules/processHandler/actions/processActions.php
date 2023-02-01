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
        //$this->registerAction('index', new action([
        //    'name' => 'Index page',
        //    'method' => '_index',
        //    'http' => [
        //        'GET',
        //        'POST',
        //    ],
        //    'required' => [
        //
        //    ],
        //    'path' => '/',
        //    'useBase' => false,
        //    'relative' => false,
        //    'template' => 'index_page.tpl',
        //    'type' => action::TYPE__DEFAULT,
        //    'acl' => [
        //        aclManager::ACL__GROUP_GUEST,
        //        aclManager::ACL__GROUP_USER,
        //        aclManager::ACL__GROUP_ADMIN,
        //    ],
        //
        //], $this));
        
        $this->registerAction('control', new action([
            'name' => 'Process manager control panel',
            'method' => '_control',
            'http' => [
                'GET',
                'POST',
            ],
            'required' => [

            ],
            'path' => '',
            'useBase' => false,
            'relative' => true,
            'template' => 'processmanager_control.tpl',
            'type' => action::TYPE__GLOBAL,
            'acl' => [
                aclManager::ACL__GROUP_ADMIN,
            ],

        ], $this));
    }

}