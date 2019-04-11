<?php
use mpcmf\system\configuration\config;

config::setConfig(__FILE__, [
    'monitoring_cache_key' => 'monitoring_processManager_exporter_cache_key',
    'monitoring_cache_key_expire' => 10,
    'process_time_out' => 5,
    'sleep' => 3
]);