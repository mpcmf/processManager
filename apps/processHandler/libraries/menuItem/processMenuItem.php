<?php

namespace mpcmf\apps\processHandler\libraries\menuItem;

use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class processMenuItem extends menuItem
{
    protected $default = [
        'name' => '',
        'description' => '',
        'state' => 'stopped',
        'mode' => 'repeatable',
        'command' => '',
        'work_dir' => '',
        'tags' => [],
        'logging' => [],
        'instances' => 1,
    ];

    protected $invisible = [
        '_id' => true,
        'forks_count' => true,
        'last_update' => true,
        'server' => true
    ];

    protected $customMenuItems = [
        'logging' => loggingMenuItem::class
    ];

    public function __construct($key, array $processData, $title)
    {
        if (empty($processData)) {
            $processData = $this->default;
        }

        $processFieldMenuItems = [];
        foreach ($processData as $filedKey => $fieldValue) {
            $key = $filedKey;
            $value = $fieldValue;
            $isVisible = !isset($this->invisible[$filedKey]);

            $menuItemClass = isset($this->customMenuItems[$filedKey]) ? $this->customMenuItems[$filedKey] : menuItem::class;

            $processFieldMenuItems[] = new $menuItemClass($key, $value, helper::formTitle($filedKey, $fieldValue), $isVisible);
        }

        parent::__construct($key, $processFieldMenuItems, $title);
    }
}