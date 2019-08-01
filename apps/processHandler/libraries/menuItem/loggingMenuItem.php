<?php

namespace mpcmf\apps\processHandler\libraries\menuItem;

use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class loggingMenuItem extends menuItem
{
    protected $default = [
        'enable' => false,
        'type' => 'file',
        'path' => '',
        'handlers' => [
            'stdout',
            'stdin'
        ]
    ];

    public function __construct($key, $loggingParams, $title)
    {
        if (empty($loggingParams) || !is_array($loggingParams)) {
            $loggingParams = $this->default;
            $title = helper::formTitle($key, $loggingParams);
        }

        foreach ($loggingParams as $fieldKey => $fieldValue) {
            $loggingParams[$fieldKey] = new menuItem($fieldKey, $fieldValue, helper::formTitle($fieldKey, $fieldValue));
        }

        parent::__construct($key, $loggingParams, $title);
    }
}