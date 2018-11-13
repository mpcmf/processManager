<?php

namespace mpcmf\apps\processHandler\libraries\processManager;

use mpcmf\apps\processHandler\libraries\processManager\config\configStorage;
use mpcmf\modules\processHandler\mappers\processMapper;
use mpcmf\modules\processHandler\models\processModel;
use React\EventLoop\LoopInterface;

class processHandler
{

    const STATE__NEW = 'new';
    const STATE__RUN = 'run';
    const STATE__RUNNING = 'running';

    const STATE__STOP = 'stop';
    const STATE__STOPPING = 'stopping';
    const STATE__STOPPED = 'stopped';

    const STATE__RESTART = 'restart';
    const STATE__RESTARTING = 'restarting';

    const STATE__REMOVE = 'remove';
    const STATE__REMOVING = 'removing';

    protected $statusesPriority = [
        process::STATUS__STOP,
        process::STATUS__RUN,
        process::STATUS__RESTART,
        process::STATUS__RESTARTING,

        process::STATUS__STOPPING,
        process::STATUS__RUNNING,
        process::STATUS__STOPPED,
        process::STATUS__EXITED,
    ];

    protected $states = [
        process::STATUS__RESTART => self::STATE__RESTART,
        process::STATUS__RESTARTING => self::STATE__RESTARTING,
        process::STATUS__RUN => self::STATE__RUN,
        process::STATUS__RUNNING => self::STATE__RUNNING,
        process::STATUS__STOP => self::STATE__STOP,
        process::STATUS__STOPPING => self::STATE__STOPPING,
        process::STATUS__STOPPED => self::STATE__STOPPED,
        process::STATUS__EXITED => self::STATE__STOPPED,
    ];

    /**
     * @var configStorage
     */
    protected $configStorage;

    protected $currentConfig = [];

    protected $loop;

    /**
     * @var server
     */
    protected $server;

    protected $mainCyclePeriodTime = 1;

    /** @var array|processModel[]  */
    protected $processPool = [
//        'test_child_creator' => [
//            'tag' => 'test_child_creator',
//            'last_started' => null,
//            'instances' => [
//                process::class,
//            ],
//            'config' => processModel::class
//        ]
    ];

    public function __construct(configStorage $configStorage, LoopInterface $loop)
    {
        $this->configStorage = $configStorage;
        $this->loop = $loop;
    }

    public function start()
    {
        $this->server = new server($this->loop);
        $this->server->runPing();
        $this->loop->addPeriodicTimer($this->mainCyclePeriodTime, function () {
            $this->mainCycle();
        });
    }

    protected function mainCycle()
    {
        $this->updateConfig();
        $this->management();
    }

    protected function management()
    {
        /** @var array|processModel[] $process */
        foreach ($this->processPool as $id => &$process) {
            $this->refresh($id);
            $currentState = $this->checkState($id, $process['config']->getState());
            error_log("{$process['config']->getCommand()}: {$currentState}");
            switch ($process['config']->getState()) {
                case self::STATE__NEW:
                case self::STATE__RUN:
                    $this->run($id);
                    $process['config']->setState(self::STATE__RUNNING);
                    $process['last_started'] = time();
                    break;

                case self::STATE__REMOVE:
                    $this->stop($id);
                    $process['config']->setState(self::STATE__REMOVING);
                    break;
                case self::STATE__STOP:
                    $this->stop($id);
                    var_dump('Set state to stoppting!');
                    $process['config']->setState(self::STATE__STOPPING);
                    break;

                case self::STATE__REMOVING:
                    if ($currentState === self::STATE__STOPPED) {
                        unset($this->processPool[$id]);
                    } else {
                        MPCMF_DEBUG && error_log("Process {$id} waiting for remove...");
                    }
                    break;

                case self::STATE__STOPPING:
                    if ($currentState === self::STATE__STOPPED) {
                        $process['config']->setState(self::STATE__STOPPED);
                    } else {
                        $this->stop($id);
                        MPCMF_DEBUG && error_log("Process {$id} waiting for stop...");
                    }
                    break;

                case self::STATE__RESTARTING:
                    if ($currentState === self::STATE__STOPPED) {
                        $process['config']->setState(self::STATE__RUN);
                        $process['last_started'] = null;
                        MPCMF_DEBUG && error_log("Process {$id} stopped in restart. Starting again!");
                    } else {
                        $this->stop($id);
                        MPCMF_DEBUG && error_log("Process {$id} waiting for restarting...");
                    }

                    break;
                case self::STATE__RUNNING:
                    $this->run($id);
                    break;

                case self::STATE__STOPPED:
                    MPCMF_DEBUG && error_log("Process {$id} in endless state: [{$process['config']->getState()}]");
                    break;
            }
        }
        $this->syncConfig();
        error_log('Result state: ' . reset($this->processPool)['config']->getState());
    }

    protected function checkState($id, $wantedState)
    {
        /** @var process $instance */
        $process =& $this->processPool[$id];
        $prio = array_flip($this->statusesPriority);

        if (count($process['instances']) > 0) {
            if ($wantedState === self::STATE__RUNNING || $wantedState === self::STATE__RUN) {
                $status = process::STATUS__RUNNING;
            } elseif ($wantedState === self::STATE__RESTARTING) {
                $status = process::STATUS__RESTARTING;
            } elseif ($wantedState === self::STATE__STOPPING) {
                $status = process::STATUS__STOPPING;
            } elseif ($wantedState === self::STATE__STOP) {
                $status = process::STATUS__STOP;
            }

            foreach ($process['instances'] as $instance) {
                $instanceStatus = $instance->getStatus();
                if ($prio[$instanceStatus] > $prio[$status]) {
                    $status = $instanceStatus;
                }
            }
        } else {
            $status = self::STATE__STOPPED;
        }

        return $this->states[$status];
    }

    protected function updateConfig()
    {
        $newConfig = $this->configStorage->getProcessesConfig($this->server->serverId);
        $changes = $this->compareConfig($newConfig);

        $this->processingChanges($changes);
        $this->syncConfig();
    }

    protected function syncConfig()
    {
        /** @var processModel[] $process */
        foreach ($this->processPool as $id => $process) {
            $this->configStorage->saveConfig($process['config']);
        }
    }

    protected function compareConfig($newConfig)
    {
        $changedStates = [
            'new' => [],
            'stop' => [],
            'run' => [],
            'restart' => [],
            'remove' => $newConfig
        ];

        /** @var processModel $processModel */
        foreach ($newConfig as $id => $processModel) {
            unset($changedStates['remove'][$id]);
            if (!isset($this->processPool[$id])) {
                $changedStates['new'][$id] = $processModel;

                continue;
            }

            /** @var array|processModel[] $process */
            $process =& $this->processPool[$id];

            if ($processModel->getState() === self::STATE__STOP && $process['config']->getState() === self::STATE__RUNNING) {
                $changedStates['stop'][$id] = $processModel;
            } elseif ($processModel->getState() === self::STATE__RUN && $process['config']->getState() === self::STATE__STOPPED) {
                $changedStates['run'][$id] = $processModel;
            } elseif ($processModel->getState() === self::STATE__RESTART && $process['config']->getState() === self::STATE__RUNNING) {
                $changedStates['restart'][$id] = $processModel;
            }

            unset($process);
        }

        return $changedStates;
    }

    protected function processingChanges($changes)
    {
        $makeNewStates = [
            self::STATE__RUN,
            self::STATE__RUNNING,
            self::STATE__RESTART,
            self::STATE__RESTARTING,
        ];

        /** @var processModel $processModel */
        foreach ($changes['new'] as $id => $processModel) {
            if (in_array($processModel->getState(), $makeNewStates)) {
                $processModel->setState(self::STATE__NEW);
            } else {
                $processModel->setState(self::STATE__STOPPED);
            }
            $this->processPool[$id] = [
                'tag' => $id,
                'instances' => [],
                'last_started' => null,
                'config' => $processModel
            ];
        }

        /** @var processModel $processModel */
        foreach ($changes['run'] as $id => $processModel) {
            $processModel->setState(self::STATE__RUN);
            $this->processPool[$id]['last_started'] = null;
            $this->processPool[$id]['config'] = $processModel;
        }

        /** @var processModel $processModel */
        foreach ($changes['stop'] as $id => $processModel) {
            $this->processPool[$id]['config'] = $processModel;
        }

        /** @var processModel $processModel */
        foreach ($changes['restart'] as $id => $processModel) {
            $this->processPool[$id]['config'] = $processModel;
        }

        /** @var processModel $processModel */
        foreach ($changes['remove'] as $id => $processModel) {
            $processModel->setState(self::STATE__REMOVE);
            $this->processPool[$id]['config'] = $processModel;
        }
    }

    protected function refresh($id)
    {
        $process =& $this->processPool[$id];

        /** @var process $instance */
        foreach ($process['instances'] as $id => $instance) {
            $status = $instance->getStatus();
            if ($status === process::STATUS__STOPPED || $status === process::STATUS__EXITED) {
                var_dump('$unset');
                unset($process['instances'][$id]);
            }
        }
    }

    protected function run($id)
    {
        $this->setLogFiles($id);
        $process =& $this->processPool[$id];
        /** @var processModel $config */
        $config =& $process['config'];
        $maxInstances = $config->getInstances();
        $mode = $config->getMode();
        $currentState = $this->checkState($id, $config->getState());

        switch ($mode) {
            case processMapper::MODE__PERIODIC:
            case processMapper::MODE__TIMER:
            case processMapper::MODE__CRON:
                error_log("Mode [{$mode}] not implemented, used [one_run] mode");

            case processMapper::MODE__ONE_RUN:
                if ($currentState === self::STATE__STOPPED && $process['last_started'] !== null) {
                    $config->setState($currentState);
                    break;
                }
            case processMapper::MODE__REPEATABLY:
                while (count($process['instances']) < $maxInstances) {
                    $instance = new process($this->loop, $config->getCommand(), $config->getWorkDir());
                    $instance->run();

                    $process['instances'][] = $instance;
                    usleep(10000);
                }

                break;
        }
    }

    protected function setLogFiles($id)
    {
        $process =& $this->processPool[$id];
        /** @var processModel $config */
        $config =& $process['config'];

        $stdErrorPaths = $config->getStdErrorPaths();
        $stdOutPaths = $config->getStdOutPaths();
        $stdErrorWsChannelIds = $config->getStdErrorWsChannelIds();
        $stdOutWsChannelIds = $config->getStdOutWsChannelIds();

        /** @var process $instance */
        foreach ($process['instances'] as $instance) {
            $instance->setStdErrorLogFiles($stdErrorPaths);
            $instance->setStdOutLogFiles($stdOutPaths);
            $instance->setStdErrorWsChannelIds($stdErrorWsChannelIds);
            $instance->setStdOutWsChannelIds($stdOutWsChannelIds);
        }
    }

    protected function stop($id)
    {
        $process =& $this->processPool[$id];

        /** @var process $instance */
        foreach ($process['instances'] as $instance) {
            $instance->stop();
        }
    }

    protected function restart($id)
    {
        $process =& $this->processPool[$id];

        /** @var process $instance */
        foreach ($process['instances'] as $instance) {
            $instance->restart();
        }
    }
}