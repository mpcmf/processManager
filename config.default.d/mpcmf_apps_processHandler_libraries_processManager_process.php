<?php

\mpcmf\system\configuration\config::setConfig(__FILE__, [
    'web_sockets' => [
        'enabled' => false,
        'web_socket_server_publish_end_point' => 'https://localhost/ws/publish'
    ]
]);