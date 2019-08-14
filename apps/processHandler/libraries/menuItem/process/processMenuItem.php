<?php

namespace mpcmf\apps\processHandler\libraries\menuItem\process;

use Codedungeon\PHPCliColors\Color;
use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\menuItem\objectEditMenuItem;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class processMenuItem extends menuItem implements objectEditMenuItem
{
    protected $default = [
        'name' => '',
        'description' => '',
        'state' => 'stopped',
        'mode' => 'repeatable',
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
        'updated_by' => true,
        'created_at' => true,
        'created_by' => true,
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

        $processKey = md5(json_encode($process));

        parent::__construct($processKey, $value, $this->formTitle($process));
    }

    public function formTitle(array $process = [])
    {
        if (empty($process)) {
            $process = $this->export();
        }

        $isLogged = isset($process['logging']['enabled']) && $process['logging']['enabled'] === true;
        $stopped = $process['state'] === 'stop' || $process['state'] === 'stopped';

        $state = ($stopped ? Color::RED : Color::GREEN) . " {$process['state']}" . Color::RESET;
        $logging = ($isLogged ? Color::GREEN : Color::RED) . 'logging' . Color::RESET;

        $title = helper::padding($state, $logging, 20);
        $title = helper::padding($title, $process['server']['host'], 20);
        $title = helper::padding($process['name'], $title, 100);

        return $title;
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
