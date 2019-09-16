<?php

namespace mpcmf\apps\processHandler\libraries\menuItem\process;

use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\menuItem\selectableEditMenuItem;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class serverMenuItem extends menuItem implements selectableEditMenuItem
{
    public function __construct(array $server, $isVisible = true)
    {
        parent::__construct('server', $server['_id'], helper::formTitle('server', $server['host']), $isVisible);
    }

    public function getToSelectItems()
    {
        return $this->getServerMenuItems();
    }

    public static function getHost($serverId)
    {
        $server = apiClient::factory()->call('server', 'getById', ['id' => $serverId])['data'];

        return $server['host'];
    }

    private function getServerMenuItems()
    {
        $serverMenuItems = [];
        $serversList = apiClient::factory()->call('server', 'getList')['data'];
        foreach ($serversList as $serverData) {
            $serverMenuItems[$serverData['host']] = $serverData['_id'];
        }

        return $serverMenuItems;
    }
}
