<?php
/**
 * mongoInstance configuration
 *
 * @author Gregory Ostrovsky <greevex@gmail.com>
 */

use mpcmf\system\configuration\config;

config::setConfig(__FILE__, [
    'localhost' => [
        'uri' => 'mongodb://localhost',
        'options' => [
            'connect' => true,
        ]
    ]
]);