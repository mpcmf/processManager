<?php

use mpcmf\system\configuration\config;

config::setConfig(__FILE__, [
    'default' => [
        'servers' => [
            [
                'host' => 'localhost',
                'port' => 11211
            ]
        ]
    ],
]);