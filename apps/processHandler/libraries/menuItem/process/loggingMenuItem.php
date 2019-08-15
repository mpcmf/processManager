<?php

namespace mpcmf\apps\processHandler\libraries\menuItem\process;

use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\menuItem\objectEditMenuItem;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class loggingMenuItem extends menuItem implements objectEditMenuItem
{
    protected $default = [
        'enabled' => false,
        'path' => '',
        'handlers' => [
            'stdout',
            'stderr'
        ]
    ];

    protected $invisible = [];

    protected $customMenuItems = [
        'enabled' => loggingEnableMenuItem::class,
        'handlers' => loggingHandlersMenuItem::class
    ];

    public function __construct($params)
    {
        foreach ($this->default as $paramKey => $item) {
            if (empty($params[$paramKey])) {
                $params[$paramKey] = $item;
            }
        }

        if (empty($params['path'])) {
            $params['path'] = '/tmp/process_' . md5(microtime()) . '.log';
        }

        $menuItems = [];
        $title = helper::formTitle('logging', $params);

        foreach ($params as $field => $value) {
            $isVisible = !isset($this->invisible[$field]);
            if (isset($this->customMenuItems[$field])) {
                $menuItems[$field] = new $this->customMenuItems[$field]($value, $isVisible);
            } else {
                $menuItems[$field] = new menuItem($field, $value, helper::formTitle($field, $value), $isVisible);
            }
        }

        parent::__construct('logging', $menuItems, $title);
    }

    public function export()
    {
        $exported = [];
        /** @var menuItem $menuItem */
        foreach ($this->value as $menuItem) {
            $exported[$menuItem->getKey()] = $menuItem->export();
        }

        return $exported;
    }
}
