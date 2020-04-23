<?php

namespace mpcmf\apps\processHandler\libraries\processManager;

use mpcmf\system\helper\io\log;
use React\EventLoop\LoopInterface;

class processPool
{
    use log;

    protected $pool = [];
    protected $loop;

    public function __construct(LoopInterface $loop)
    {
        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        $this->loop = $loop;
    }

    public function add(process $process)
    {
        $this->pool[] = $process;
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
        $this->checkProcessesLoop();
    }

    public function getPoolCount()
    {
        return count($this->pool);
    }

    protected function checkProcessesLoop()
    {
        $this->loop->addPeriodicTimer(1, function () {
            pcntl_signal_dispatch();
            if (empty($this->pool)) {
                self::log()->addInfo('All processes done!');
            }
            /** @var process $process */
            foreach ($this->pool as $key => $process) {
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