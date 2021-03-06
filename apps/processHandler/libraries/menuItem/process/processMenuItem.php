<?php

namespace mpcmf\apps\processHandler\libraries\menuItem\process;

use Codedungeon\PHPCliColors\Color;
use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\api\locker;
use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\communication\operationResult;
use mpcmf\apps\processHandler\libraries\menuItem\objectEditMenuItem;
use mpcmf\apps\processHandler\libraries\processManager\processHandler;
use mpcmf\modules\processHandler\mappers\processMapper;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class processMenuItem extends menuItem implements objectEditMenuItem
{
    protected $default = [
        'name' => '',
        'description' => '',
        'state' => processHandler::STATE__STOPPED,
        'mode' => processMapper::MODE__REPEATABLE,
        'period' => 0,
        'command' => '',
        'work_dir' => '',
        'tags' => [],
        'logging' => [],
        'server' => null,
        'instances' => 1,
    ];

    protected $invisible = [
        '_id' => true,
        'forks_count' => true,
        'updated_at' => true,
        'created_at' => true,
        'std_out' => true,
        'std_error' => true,
    ];

    protected $customMenuItems = [
        'logging' => loggingMenuItem::class,
        'state' => stateMenuItem::class,
        'mode' => modeMenuItem::class,
        'tags' => tagsMenuItem::class,
        'server' => serverMenuItem::class
    ];

    public function __construct(array $process = [])
    {
        $this->default['logging'] = [
            'path' => "file:///tmp/process_{$process['_id']}.log"
        ];

        foreach ($this->default as $field => $item) {
            if (empty($process[$field])) {
                $process[$field] = $item;
            }
        }

        $value = [];
        foreach ($process as $field => $item) {
            $isVisible = !isset($this->invisible[$field]);
            if (isset($this->customMenuItems[$field])) {
                $value[$field] = new $this->customMenuItems[$field]($item, $isVisible);
            } else {
                $value[$field] = new menuItem($field, $item, helper::formTitle($field, $item), $isVisible);
            }
        }

        parent::__construct($process['_id'], $value, $this->formTitle($process));
    }

    public function formTitle(array $process = [])
    {
        if (empty($process)) {
            $process = $this->export();
        }

        $server = isset($process['server']['host']) ? $process['server']['host'] : serverMenuItem::getHost($process['server']);

        $isLogged = isset($process['logging']['enabled']) && $process['logging']['enabled'] === true;
        $stopped = $process['state'] === processHandler::STATE__STOP || $process['state'] === processHandler::STATE__STOPPED;

        $state = ($stopped ? Color::RED : Color::GREEN) . " {$process['state']}" . Color::RESET;
        $logging = ($isLogged ? Color::GREEN : Color::RED) . 'logging' . Color::RESET;

        $title = helper::padding($state, $logging, 20);
        $title = helper::padding($title, $server, 20);
        $title = helper::padding($process['name'], $title, 100);

        return $title;
    }

    public function save()
    {
        $process = $this->export();

        $updating = false;
        if (!empty($process['_id'])) {
            $response = apiClient::factory()->call('process', 'getById', ['id' => $process['_id']]);
            $updating = $response['status'];
        }

        if ($updating) {
            $result = apiClient::factory()->call('process', 'update', ['ids' => [$process['_id']], 'fields_to_update' => $process]);
        } else {
            $result = apiClient::factory()->call('process', 'add', ['object' => $process]);
        }

        $success = $result['status'];
        if ($success) {
            locker::lockWrite([$process['_id']]);
        }

        $errors = isset($result['data']['errors']) ? $result['data']['errors'] : [];

        operationResult::notify($success, $errors);
    }

    public function reload()
    {
        $process = $this->export();
        $result = apiClient::factory()->call('process', 'getById', ['id' => $process['_id']]);

        if (!$result['status']) {
            return false;
        }

        $process = $result['data'];
        $result = apiClient::factory()->call('server', 'getById', ['id' => $process['server']]);
        if (!$result['status']) {
            return false;
        }

        $process['server'] = $result['data'];

        self::__construct($process);

        return true;
    }

    public function export()
    {
        $exported = [];
        /** @var menuItem $menuItem */
        foreach ($this->value as $menuItem) {
            $key = $menuItem->getKey();
            $value = $menuItem->export();
            if ($key === 'period' || $key === 'instances') {
                $value = (int) $value;
            }
            $exported[$key] = $value;
        }

        return $exported;
    }
}
