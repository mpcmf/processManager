<?php

namespace mpcmf\apps\processHandler\libraries\processManager;

use mpcmf\apps\processHandler\libraries\processManager\config\configStorage;
use mpcmf\apps\processHandler\libraries\stats\stats;
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
    const STATE__READY_TO_REMOVE_FROM_DB = 'ready_to_remove_from_db';

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

    protected $mainCyclePeriodTime = 2;

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

    protected $stopAll = false;

    public function __construct(configStorage $configStorage, LoopInterface $loop)
    {
        $this->configStorage = $configStorage;
        $this->loop = $loop;
        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGINT, [$this, 'signalHandler']);
        $this->server = new server($this->loop);
    }

    public function start()
    {
        $this->server->runPing();
        $this->loop->addPeriodicTimer($this->mainCyclePeriodTime, function () {
            $this->mainCycle();
        });
    }

    protected function mainCycle()
    {
        pcntl_signal_dispatch();
        $this->updateConfig();
        $this->management();
    }

    protected function management()
    {
        if (empty($this->processPool)) {
            error_log('Empty processes pool!');

            return;
        }

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

                    break;
                case self::STATE__REMOVE:
                    $this->stop($id);
                    $process['config']->setState(self::STATE__REMOVING);

                    break;
                case self::STATE__STOP:
                    $this->stop($id);
                    error_log('Setting state to stop!');
                    $process['config']->setState(self::STATE__STOPPING);

                    break;
                case self::STATE__REMOVING:
                    if ($currentState === self::STATE__STOPPED) {
                        $process['config']->setState(self::STATE__READY_TO_REMOVE_FROM_DB);
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
                case self::STATE__RESTART:
                    if ($currentState === self::STATE__STOPPED) {
                        $process['config']->setState(self::STATE__RUN);
                        if ($process['config']->getMode() !== processMapper::MODE__PERIODIC) {
                            $process['last_started'] = null;
                        }
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
    }

    protected function checkState($id, $wantedState)
    {
        /** @var process $instance */
        $process =& $this->processPool[$id];
        $prio = array_flip($this->statusesPriority);

        if (count($process['instances']) > 0) {
            if ($wantedState === self::STATE__RUNNING || $wantedState === self::STATE__RUN) {
                $status = process::STATUS__RUNNING;
            } elseif ($wantedState === self::STATE__RESTARTING || $wantedState === self::STATE__RESTART) {
                $status = process::STATUS__RESTARTING;
            } elseif ($wantedState === self::STATE__STOPPING) {
                $status = process::STATUS__STOPPING;
            } elseif ($wantedState === self::STATE__STOP) {
                $status = process::STATUS__STOP;
            } elseif ($wantedState === self::STATE__REMOVE || $wantedState === self::STATE__REMOVING) {
                $status = process::STATUS__STOPPING;
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
        try {
            $newConfig = $this->configStorage->getProcessesConfig($this->server->serverId);
        } catch (\Exception $exception) {
            error_log("[Exception] on getting config from db {$exception->getMessage()}");
            $newConfig = [];
        }
        $changes = $this->compareConfig($newConfig);

        $this->processingChanges($changes);
        $this->syncConfig();
    }

    protected function syncConfig()
    {
        /** @var processModel[] $process */
        foreach ($this->processPool as $id => $process) {
            try {
                if ($process['config']->getState() === self::STATE__READY_TO_REMOVE_FROM_DB) {
                    $this->configStorage->removeConfig($process['config']);
                    unset($this->processPool[$id]);
                    continue;
                }
                $forksCount = 0;
                /** @var process $processInstance */
                foreach ($process['instances'] as $processInstance) {
                    $forksCount += $processInstance->getForksCount();
                }
                $process['config']->setForksCount($forksCount);
                $this->configStorage->saveConfig($process['config']);
            } catch (\Exception $exception) {
                error_log("[Exception] on syncing config! {$exception->getMessage()}");
            }
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

            //set config from db
            $process['config']->setLogging($processModel->getLogging());
            $process['config']->setCommand($processModel->getCommand());
            $process['config']->setWorkDir($processModel->getWorkDir());
            $process['config']->setDescription($processModel->getDescription());
            $process['config']->setMode($processModel->getMode());
            $process['config']->setTags($processModel->getTags());
            $process['config']->setName($processModel->getName());
            $process['config']->setPeriod($processModel->getPeriod());

            if ($processModel->getState() === self::STATE__STOP && $process['config']->getState() === self::STATE__RUNNING) {
                $changedStates['stop'][$id] = $processModel;
            } elseif ($processModel->getState() === self::STATE__RUNNING && $processModel->getInstances() !== $process['config']->getInstances()) {
                $processModel->setState(process::STATUS__RESTART);
                $changedStates['restart'][$id] = $processModel;
            } elseif ($processModel->getState() === self::STATE__RUN && $process['config']->getState() === self::STATE__STOPPED) {
                $changedStates['run'][$id] = $processModel;
            } elseif ($processModel->getState() === self::STATE__RESTART && $process['config']->getState() === self::STATE__RUNNING) {
                $changedStates['restart'][$id] = $processModel;
            } elseif ($processModel->getState() === self::STATE__REMOVE) {
                $changedStates['remove'][$id] = $processModel;
            }
            $process['config']->setInstances($processModel->getInstances());

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
            if ($processModel->getMode() !== processMapper::MODE__PERIODIC) {
                $this->processPool[$id]['last_started'] = null;
            }
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
        foreach ($process['instances'] as $instanceId => $instance) {
            $status = $instance->getStatus();
            if ($status === process::STATUS__STOPPED || $status === process::STATUS__EXITED) {
                error_log('Instance of process is stopped or exited. Deleting from instances collection');
                unset($process['instances'][$instanceId]);
            }
        }
    }

    protected function run($id)
    {
        $this->setLoggingParams($id);
        $process =& $this->processPool[$id];
        /** @var processModel $config */
        $config =& $process['config'];
        $maxInstances = $config->getInstances();
        $mode = $config->getMode();
        $currentState = $this->checkState($id, $config->getState());

        switch ($mode) {
            case processMapper::MODE__TIMER:
            case processMapper::MODE__CRON:
                error_log("Mode [{$mode}] not implemented, used [one_run] mode");

            case processMapper::MODE__ONE_RUN:
                if ($currentState === self::STATE__STOPPED && $process['last_started'] !== null) {
                    $config->setState($currentState);
                    break;
                }
            case processMapper::MODE__PERIODIC:
                $instancesCount = count($process['instances']);
                if ($instancesCount != 0) {
                    error_log('Instances has no finished yet');

                    break;
                }
                $nextStartTime = $process['last_started'] + $config->getPeriod();
                if ($nextStartTime > time()) {
                    error_log('The time has not come yet. Start time is ' . date('Y-m-d H:i:s', $nextStartTime));

                    break;
                }
            case processMapper::MODE__REPEATABLE:
                while (count($process['instances']) < $maxInstances && $this->stopAll === false) {
                    $instance = new process($this->loop, $config->getCommand(), $config->getWorkDir());
                    $instance->run();

                    stats::start($config->getCommand(), $config->getMode(), $config->getInstances(), $this->server->getHostName());

                    $process['instances'][] = $instance;
                    $process['last_started'] = $instance->getStartedAt();

                    usleep(10000);
                }

                break;
        }
    }

    protected function setLoggingParams($id)
    {
        $process = $this->processPool[$id];

        /** @var processModel $config */
        $config = $process['config'];
        $params = $config->getLogging();

        /** @var process $instance */
        foreach ($process['instances'] as $instance) {
            if (!$params['enabled']) {
                $instance->setStdOut([]);
                $instance->setStdError([]);

                continue;
            }

            if (in_array('stdout', $params['handlers'])) {
                $instance->setStdOut([$params['path']]);
            }
            if (in_array('stderr', $params['handlers'])) {
                $instance->setStdError([$params['path']]);
            }
        }
    }

    protected function stopProcessHandler()
    {
        foreach ($this->processPool as $process) {
            /** @var processModel $config */
            $config = $process['config'];
            /** @var process $instance */
            foreach ($process['instances'] as $instance) {
                $instance->stop();
                stats::stop($config->getCommand(), $config->getMode(), $config->getInstances(), $this->server->getHostName());
            }
        }
        $this->stopAll = true;

        $this->loop->addPeriodicTimer(1, function () {
            if (!$this->isAllStopped()) {
                error_log('Waiting for stopping all processes...');
                return;
            }
            exit("All processes stopped! Exit!\n");
        });
    }

    protected function isAllStopped()
    {
        foreach ($this->processPool as $process) {
            /** @var process $instance */
            foreach ($process['instances'] as $instance) {
                if ($instance->getStatus() !== process::STATUS__STOPPED) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function stop($id)
    {
        $process =& $this->processPool[$id];

        /** @var processModel $config */
        $config = $process['config'];

        /** @var process $instance */
        foreach ($process['instances'] as $instance) {
            $instance->stop();
            stats::stop($config->getCommand(), $config->getMode(), $config->getInstances(), $this->server->getHostName());
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

    /**
     * signal handler
     *
     * @param integer $_signal
     */
    public function signalHandler($_signal = SIGTERM)
    {
        switch ($_signal) {
            case SIGTERM:
                error_log('SigTerm!');
                $this->stopProcessHandler();
                break;
            case SIGINT:
                error_log('SigInt!');
                $this->stopProcessHandler();
                break;
        }
    }

    /**
     * @return server
     */
    public function getServer()
    {
        return $this->server;
    }
}