<?php

namespace mpcmf\apps\processHandler\libraries\processManager;

use mpcmf\system\helper\io\log;
use React\EventLoop\LoopInterface;

class processPoolManager
{
    use log;

    protected $maxQueue = 0;
    protected $maxThreads = 1;
    protected $pool = [];
    protected $queue = [];
    protected $loop;
    protected $onRefresh;
    protected $refreshInterval = 1.0;

    public function __construct(LoopInterface $loop)
    {
        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        $this->loop = $loop;
    }

    public function add(process $process, $key = null)
    {
        if(count($this->pool) >= $this->maxThreads && count($this->queue) >= $this->maxQueue) {
            return false;
        }
        if ($key) {
            $this->queue[$key] = $process;
            
            return true;
        }
        $this->queue[] = $process;
        
        return true;
    }

    public function call($method, $params = [])
    {
        /** @var process $process */
        foreach ($this->pool as $process) {
            $process->$method($params);
        }
    }

    public function run()
    {
        $this->call('run');
        $this->refreshLoop();
    }

    public function getPoolCount()
    {
        return count($this->pool);
    }

    /**
     * Set max threads length
     *
     * @param int $maxThreads
     * @return mixed
     */
    public function setMaxThreads($maxThreads)
    {
        return $this->maxThreads = $maxThreads;
    }

    /**
     * Set max queue length
     *
     * @param $maxQueue
     * @return mixed
     */
    public function setMaxQueue($maxQueue)
    {
        return $this->maxQueue = $maxQueue;
    }

    /**
     * Get max queue length
     *
     * @return int
     */
    public function getMaxQueue()
    {
        return $this->maxQueue;
    }

    public function onRefresh(callable $callback)
    {
        $this->onRefresh = $callback;
    }

    public function setRefreshInterval($refreshInterval)
    {
        $this->refreshInterval = $refreshInterval;
    }

    public function hasItem($key)
    {
        return isset($this->pool[$key]);
    }

    protected function refreshLoop()
    {
        $this->loop->addPeriodicTimer($this->refreshInterval, function () {
            /**
             * @note add threads in pool from queue
             */
            while(count($this->queue) > 0 && count($this->pool) < $this->maxThreads) {
                /** @var process $newProcess */
                $newProcess = array_shift($this->queue);
                $newProcess->run();
                $this->pool[] = $newProcess;
            }

            pcntl_signal_dispatch();
            if (empty($this->pool)) {
                self::log()->addInfo('All processes done!');
            }
            /** @var process $process */
            foreach ($this->pool as $key => $process) {
                if ($this->onRefresh !== null) {
                    $onRefresh = $this->onRefresh;
                    $onRefresh($key, $process);
                }
                $status = $process->getStatus();
                if ($status === process::STATUS__EXITED || $status === process::STATUS__STOPPED) {
                    self::log()->addInfo("Process {$process->getCommand()} is done!");
                    unset($this->pool[$key]);
                }
            }
        });
    }

    public function stop()
    {
        $this->call('stop');
        $this->loop->addPeriodicTimer(1, function () {
            if (empty($this->pool)) {
                self::log()->addInfo('Stopped!');
                $this->loop->stop();
            }
        });
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
                $this->stop();
                break;
        }
    }
}