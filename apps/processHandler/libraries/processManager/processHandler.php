<?php

namespace mpcmf\apps\processHandler\libraries\processManager;

use mpcmf\apps\processHandler\libraries\processManager\config\configStorage;
use mpcmf\modules\processHandler\mappers\processMapper;
use mpcmf\modules\processHandler\models\processModel;
use React\EventLoop\LoopInterface;

class processHandler
{
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
        $newPool = $this->getNewPool();
        foreach ($newPool as $id => $newProcess) {
            $newState = $newProcess['config']->getState();
            if (($newState === process::STATUS__RUN || $newState === process::STATUS__RUNNING || $newState === process::STATUS__RESTART) && !isset($this->processPool[$id])) {
                $this->processPool[$id] = $newProcess;
                $this->run($id);
            } elseif ($newState === process::STATUS__STOP && isset($this->processPool[$id])) {
                $this->stop($id);
            }  elseif ($newState === process::STATUS__RUNNING && isset($this->processPool[$id]) && $newProcess['config']->getInstances() !== $this->processPool[$id]['config']->getInstances()) {
                $this->processPool[$id]['config']->setInstances($newProcess['config']->getInstances());
                $this->restart($id);
            } elseif ($newState === process::STATUS__RESTART && isset($this->processPool[$id])) {
                $this->restart($id);
            } elseif (isset($this->processPool[$id])) {
                $this->refresh($id, $newProcess);
            }
        }
        $this->syncConfig();
    }

    protected function refresh($id, $newProcess)
    {
        $this->processPool[$id]['config']->setStdOutPaths($newProcess['config']->getStdOutPaths());
        $this->processPool[$id]['config']->setStdErrorPaths($newProcess['config']->getStdErrorPaths());
        $this->processPool[$id]['config']->setStdOutWsChannelIds($newProcess['config']->getStdErrorWsChannelIds());
        $this->processPool[$id]['config']->setStdErrorWsChannelIds($newProcess['config']->getStdErrorWsChannelIds());
        $this->processPool[$id]['config']->setStdErrorWsChannelIds($newProcess['config']->getStdErrorWsChannelIds());


        /** @var process $instance */
        foreach ($this->processPool[$id]['instances'] as $key => $instance) {
            $status = $instance->getStatus();
            if ($status === process::STATUS__STOPPED || $status === process::STATUS__EXITED) {
                var_dump('$unset');
                unset($this->processPool[$id]['instances'][$key]);
            }
        }
    }

    protected function getNewPool()
    {
        try {
            $newConfig = $this->configStorage->getProcessesConfig($this->server->serverId);
        } catch (\Exception $exception) {
            error_log("[Exception] on reading config! {$exception->getMessage()}");
            error_log('Setting old config!');

            return $this->processPool;
        }

        $pool = [];
        foreach ($newConfig as $processModel) {
            $id = (string) $processModel->getIdValue();
            $pool[$id]['config'] = $processModel;
            $pool[$id]['instances'] = [];
        }

        return $pool;
    }

    protected function syncConfig()
    {
        /** @var processModel[] $process */
        foreach ($this->processPool as $id => $process) {
            $this->configStorage->saveConfig($process['config']);
        }
    }

    protected function run($id)
    {
        $mode = $this->processPool[$id]['config']->getMode();
        switch ($mode) {
            case processMapper::MODE__PERIODIC:
            case processMapper::MODE__TIMER:
            case processMapper::MODE__CRON:
                error_log("Mode [{$mode}] not implemented, used [one_run] mode");

            case processMapper::MODE__ONE_RUN:

            case processMapper::MODE__REPEATABLE:
                while (count($this->processPool[$id]['instances']) < $this->processPool[$id]['config']->getInstances()) {
                    $instance = new process($this->loop, $this->processPool[$id]['config']->getCommand(), $this->processPool[$id]['config']->getWorkDir());
                    $instance->setStdErrorLogFiles($this->processPool[$id]['config']->getStdErrorPaths());
                    $instance->setStdOutLogFiles($this->processPool[$id]['config']->getStdOutPaths());
                    $instance->setStdErrorWsChannelIds($this->processPool[$id]['config']->getStdErrorWsChannelIds());
                    $instance->setStdOutWsChannelIds($this->processPool[$id]['config']->getStdOutWsChannelIds());
                    $instance->run();

                    $this->processPool[$id]['config']->setState(process::STATUS__RUNNING);
                    $this->processPool[$id]['instances'][] = $instance;
                    usleep(10000);
                }

                break;
        }
    }

    protected function stop($id)
    {
        foreach ($this->processPool[$id]['instances'] as $process) {
            $process->stop();
        }
        $this->processPool[$id]['config']->setState(process::STATUS__STOPPED);
        $this->configStorage->saveConfig($this->processPool[$id]['config']);
        unset($this->processPool[$id]);
        var_dump('Unset process from pool');
    }

    protected function restart($id)
    {
        foreach ($this->processPool[$id]['instances'] as $process) {
            $process->stop();
        }
        $this->processPool[$id]['config']->setState(process::STATUS__RUN);
        $this->configStorage->saveConfig($this->processPool[$id]['config']);
        unset($this->processPool[$id]);
    }
}