<?php
/**
 * @author greevex
 * @date   : 11/16/12 5:11 PM
 */

use mpcmf\modules\processHandler\mappers\serverMapper;

\mpcmf\system\configuration\config::setConfig(__FILE__, [
    'storage' => [
        'configSection' => 'default',
        'db' => 'processHandler',
        'collection' => 'server',
        'indices' => [
            [
                'keys' => [
                    serverMapper::FIELD__HOST => 1
                ],
                'options' => [
                    'unique' => true
                ]
            ]
        ]
    ]
]);