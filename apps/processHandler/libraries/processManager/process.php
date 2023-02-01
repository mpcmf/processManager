<?php

namespace mpcmf\apps\processHandler\libraries\processManager;

use React\EventLoop\LoopInterface;

class process
{
    const STATUS__NONE = 'none';

    const STATUS__RUN = 'run';
    const STATUS__RUNNING = 'running';

    const STATUS__RESTART = 'restart';
    const STATUS__RESTARTING = 'restarting';

    const STATUS__STOP = 'stop';
    const STATUS__STOPPING = 'stopping';
    const STATUS__STOPPED = 'stopped';

    const STATUS__EXITED = 'exited';

    const TIMEOUT_SECONDS = 300;

    /**
     * @var int
     */
    protected $pid = -1;

    protected $gid;

    protected $command;

    protected $workDir;

    protected $status;

    protected $exitCode = 0;

    protected $receivedSignal;

    protected $loop;

    protected $startedAt;

    protected $stoppedAt;
    
    protected $forceKill = false;

    protected $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    protected $pipes = [];

    protected $processDescriptor;

    protected $checkEvery = 2;

    public function __construct(LoopInterface $loop, $command, $workDir = null)
    {
        $this->command = $command;
        $this->workDir = $workDir;
        $this->loop = $loop;
    }

    protected function check()
    {
        switch ($this->status) {
            case self::STATUS__RUNNING:
                $this->checkStatus();
                break;
            case self::STATUS__RUN:
                $this->start();
                break;
            case self::STATUS__EXITED:
            case self::STATUS__STOP:
                $this->kill();
                break;
            case self::STATUS__RESTART:
                $this->status = self::STATUS__RESTARTING;
                $this->kill();
                $this->start();
                break;
        }

        return $this->status;
    }

    protected function checkStatus()
    {
        MPCMF_DEBUG && error_log('Check process status...');

        $processStatus = proc_get_status($this->processDescriptor);

        if (!$processStatus['running'] || $processStatus['signaled'] || $processStatus['stopped']) {
            $this->status = self::STATUS__EXITED;
            $this->pid = -1;

            $this->exitCode = $processStatus['exitcode'];
            if ($processStatus['signaled']) {
                $this->receivedSignal = $processStatus['termsig'];
            } elseif ($processStatus['stopped']) {
                $this->receivedSignal = $processStatus['stopsig'];
            } else {
                $this->receivedSignal = 0;
            }
        }
    }

    protected function start()
    {
        if ($this->status === self::STATUS__RUNNING) {
            error_log('Process already run!');

            return;
        }
        error_log('Starting process');

        $this->exitCode = 0;
        $this->processDescriptor = proc_open($this->command, $this->descriptors, $this->pipes, $this->workDir);
        $processStatus = proc_get_status($this->processDescriptor);

        if ($processStatus['running']) {
            error_log("Process running with pid [{$processStatus['pid']}]!");
            $this->status = self::STATUS__RUNNING;
            $this->startedAt = time();
            $this->pid = $processStatus['pid'];
            posix_setpgid($processStatus['pid'], $processStatus['pid']);
            $this->gid = $processStatus['pid'];
            $this->exitCode = -1;
            $this->loop->addPeriodicTimer($this->checkEvery, function ($timer) {
                $this->check();
                if ($this->status === self::STATUS__STOPPED) {
                    $this->loop->cancelTimer($timer);
                }
            });

            return;
        }

        $this->status = self::STATUS__EXITED;
        $this->pid = -1;
        $this->exitCode = $processStatus['exitcode'];
    }
    
    public function setStdOut(array $destinations)
    {
        if(empty($destinations)) {
            return;
        }
        $parsed = parse_url($destinations[0]);
        if(!empty($parsed['scheme']) && $parsed['scheme'] !== 'file') {
            error_log("not using {$destinations[0]} as stdout");
            return;
        }

        $this->descriptors[1] = fopen($parsed['path'], 'ab');
    }

    public function setStdError(array $destinations)
    {
        if(empty($destinations)) {
            return;
        }
        $parsed = parse_url($destinations[0]);
        if(!empty($parsed['scheme']) && $parsed['scheme'] !== 'file') {
            error_log("not using {$destinations[0]} as stderr");
            return;
        }

        $this->descriptors[2] = fopen($parsed['path'], 'ab');
    }

    protected function kill($force = false)
    {
        $this->forceKill = $force;
        if ($this->status === self::STATUS__STOPPING) {
            return;
        }

        if (is_resource($this->processDescriptor)) {
            proc_terminate($this->processDescriptor, SIGTERM);
        }
        error_log('proc terminated');

        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                $closed = fclose($pipe);
                if(!$closed) {
                    error_log('pipe not closed!');
                }
            }
        }
        error_log('pipes closed');
        if (is_resource($this->processDescriptor)) {
            //blocking flow
            //Do not uncomment! it really blocks under certain circumstances, for example when restarting process from gui
            //proc_close($this->processDescriptor);
        }
        error_log('pd closed');

        $this->status = self::STATUS__STOPPING;
        posix_kill(-$this->gid, SIGTERM);
        $this->loop->addPeriodicTimer(1, function ($timer) {
            static $attempts = 15;
            
            if($this->forceKill) {
                error_log("FORCE KILLING GROUP {$this->gid}");
                posix_kill(-$this->gid, SIGKILL);
                $stopped = true;
            } else {
                $stopped = false;
                error_log("Sent -15 to group {$this->gid}");
                if (!posix_kill(-$this->gid, SIGTERM)) {
                    $stopped = true;
                }
                if (!$stopped && --$attempts === 0) {
                    posix_kill(-$this->gid, SIGKILL);
                    error_log("Sent -9 to group {$this->gid}");
                    $stopped = true;
                }
            }
            if ($stopped) {
                $this->status = self::STATUS__STOPPED;
                $this->stoppedAt = time();
                $this->pid = -1;
                $this->loop->cancelTimer($timer);
            }
        });
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function run()
    {
        $this->status = self::STATUS__RUN;
        $this->check();
    }

    public function restart()
    {
        $this->status = self::STATUS__RESTART;
        $this->check();
    }

    public function stop($force = false)
    {
        if ($this->status !== self::STATUS__STOPPING) {
            $this->status = self::STATUS__STOP;
        }
        if(!$force) {
            $this->check();
            return;
        }
        $this->kill(true);
    }

    public function getChildPids($pid = null)
    {
        if ($pid === null) {
            $pid = $this->getPid();
        }

        $pids = array_filter(explode("\n", shell_exec("pgrep -P {$pid}")));
//        foreach ($pids as $childPid) {
//            foreach ($this->getChildPids($childPid) as $onePid) {
//                $pids[] = $onePid;
//            }
//        }

        return $pids;
    }

    public function getForksCount()
    {
        if (!$this->gid) {
            return 0;
        }

        return substr_count(shell_exec("pgrep -g {$this->gid}"), "\n");
    }

    public function getStartedAt()
    {
        return $this->startedAt;
    }

    public function getStoppedAt()
    {
        return $this->stoppedAt;
    }
}