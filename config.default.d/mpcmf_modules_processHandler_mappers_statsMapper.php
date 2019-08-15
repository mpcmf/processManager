<?php
/**
 * @author greevex
 * @date   : 11/16/12 5:11 PM
 */


use mpcmf\modules\processHandler\mappers\statsMapper;

\mpcmf\system\configuration\config::setConfig(__FILE__, [
    'storage' => [
        'configSection' => 'localhost',
        'db' => 'processHandler',
        'collection' => 'process_stats',
        'indices' => [
            [
                'keys' => [
                    statsMapper::FIELD__PROCESS_COMMAND => 1
                ]
            ],
            [
                'keys' => [
                    statsMapper::FIELD__SERVER => 1
                ]
            ],
            [
                'keys' => [
                    statsMapper::FIELD__ACTION_AT => 1
                ]
            ],
            [
                'keys' => [
                    statsMapper::FIELD__ACTION_TYPE => 1
                ]
            ]
        ]
    ]
]);