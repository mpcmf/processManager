<?php

namespace mpcmf\apps\processHandler\libraries\processManager;

use React\EventLoop\LoopInterface;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;

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
     * @var $stdin WritableResourceStream
     */
    protected $stdin;

    /**
     * @var $stdout ReadableResourceStream
     */
    protected $stdout;

    /**
     * @var $stderr ReadableResourceStream
     */
    protected $stderr;

    protected $stdOutLogFiles = [];
    protected $stdErrorLogFiles = [];
    protected $checkEvery = 1;

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
        error_log('Check process status...');

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
        $this->stdin  = new WritableResourceStream($this->pipes[0], $this->loop);
        $this->stdout = new ReadableResourceStream($this->pipes[1], $this->loop);
        $this->stdout->on('data', function ($data) {
            foreach ($this->stdOutLogFiles as $logFile) {
                file_put_contents($logFile, $data, FILE_APPEND);
            }
        });
        $this->stderr = new ReadableResourceStream($this->pipes[2], $this->loop);
        $this->stderr->on('data', function ($data) {
            foreach ($this->stdErrorLogFiles as $logFile) {
                file_put_contents($logFile, $data, FILE_APPEND);
            }
        });
    }

    public function addStdOutLogFile($filePath)
    {
        $this->stdOutLogFiles[$filePath] = $filePath;

        return true;
    }

    public function setStdOutLogFiles(array $filesPaths)
    {
        $this->stdOutLogFiles = array_combine($filesPaths, $filesPaths);

        return true;
    }

    public function removeStdOutLogFile($filePath)
    {
        if (isset($this->stdOutLogFiles[$filePath])) {
            unset($this->stdOutLogFiles[$filePath]);
        }

        return true;
    }

    public function addStdErrorLogFile($filePath)
    {
        $this->stdOutLogFiles[$filePath] = $filePath;

        return true;
    }

    public function setStdErrorLogFiles(array $filesPaths)
    {
        $this->stdErrorLogFiles = array_combine($filesPaths, $filesPaths);

        return true;
    }

    public function removeStdErrorLogFile($filePath)
    {
        if (isset($this->stdOutLogFiles[$filePath])) {
            unset($this->stdOutLogFiles[$filePath]);
        }

        return true;
    }

    protected function kill()
    {
//        $childPids = $this->getChildPids();
//        $childPids[] = $this->getPid();

        proc_terminate($this->processDescriptor, SIGTERM);
        error_log('proc terminated');

//        foreach ($childPids as $childPid) {
//            error_log("Term child {$childPid}");
//            posix_kill($childPid, SIGTERM);
//        }
//
//        sleep(1);
//
//        foreach ($childPids as $childPid) {
//            error_log("Kill child {$childPid}");
//            posix_kill($childPid, SIGKILL);
//        }

        foreach ($this->pipes as $pipe) {
            fclose($pipe);
        }
        error_log('pipes closed');
        proc_close($this->processDescriptor);
        error_log('pd closed');

        $this->status = self::STATUS__STOPPED;
        $this->pid = -1;
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