<?php

namespace mpcmf\apps\processHandler\libraries\processManager;

use mpcmf\apps\processHandler\libraries\streamRouter\streamRouter;
use mpcmf\system\configuration\config;
use React\EventLoop\LoopInterface;
use React\Stream\Stream;

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

    protected $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
//        0 => ['file', 'php://stdin', 'r'],
//        1 => ['file', 'php://stdout', 'a'],
//        2 => ['file', 'php://stderr', 'a']
    ];

    protected $pipes = [];

    protected $processDescriptor;

    /**
     * @var $stdout Stream
     */
    protected $stdout;

    /**
     * @var $stderr Stream
     */
    protected $stderr;

    protected $stdOut = [];
    protected $stdError = [];
    protected $enabledWs = false;
    protected $checkEvery = 1;

    protected $stdOutStreamRouter;
    protected $stdErrorStreamRouter;

    public function __construct(LoopInterface $loop, $command, $workDir = null)
    {
        $this->command = $command;
        $this->workDir = $workDir;
        $this->loop = $loop;
        $this->stdOutStreamRouter = new streamRouter($loop);
        $this->stdErrorStreamRouter = new streamRouter($loop);
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
            $this->initStreams();

            return;
        }

        $this->status = self::STATUS__EXITED;
        $this->pid = -1;
        $this->exitCode = $processStatus['exitcode'];
    }

    protected function initStreams()
    {
        $this->stdOutStreamRouter->run($this->pipes[1]);
        $this->stdErrorStreamRouter->run($this->pipes[2]);
    }

    public function addStdOut($destination)
    {
        return $this->stdOutStreamRouter->addConsumer($destination);
    }

    public function setStdOut(array $destinations)
    {
        return $this->stdOutStreamRouter->setConsumers($destinations);
    }

    public function removeStdOut($destination)
    {
        return $this->stdOutStreamRouter->removeConsumer($destination);
    }

    public function addStdError($destination)
    {
        return $this->stdErrorStreamRouter->addConsumer($destination);
    }

    public function setStdError(array $destinations)
    {
        return $this->stdErrorStreamRouter->setConsumers($destinations);
    }

    public function removeStdError($destination)
    {
        return $this->stdErrorStreamRouter->removeConsumer($destination);
    }

    protected function kill()
    {
        if ($this->status === self::STATUS__STOPPING) {
            return;
        }

        if (is_resource($this->processDescriptor)) {
            proc_terminate($this->processDescriptor, SIGTERM);
        }
        error_log('proc terminated');

        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        error_log('pipes closed');
        if (is_resource($this->processDescriptor)) {
            proc_close($this->processDescriptor);
        }
        error_log('pd closed');

        $this->status = self::STATUS__STOPPING;
        posix_kill(-$this->gid, SIGTERM);
        $this->loop->addPeriodicTimer(1, function ($timer) {
            static $attempts = 20;

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
            if ($stopped) {
                $this->status = self::STATUS__STOPPED;
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

    public function stop()
    {
        $this->status = self::STATUS__STOP;
        $this->check();
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
}