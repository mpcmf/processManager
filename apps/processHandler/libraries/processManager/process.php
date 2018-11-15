<?php

namespace mpcmf\apps\processHandler\libraries\processManager;

use mpcmf\system\configuration\config;
use mpcmf\system\helper\service\signalHandler;
use mpcmf\system\net\reactCurl;
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

    protected $stdOutLogFiles = [];
    protected $stdErrorLogFiles = [];
    protected $stdOutWsChannelIds = [];
    protected $stdErrorWsChannelIds = [];
    protected $webSocketServerPublishEndPoint;
    protected $enabledWs = false;
    protected $checkEvery = 1;

    public function __construct(LoopInterface $loop, $command, $workDir = null)
    {
        $signalHandler = signalHandler::getInstance();
        $signalHandler->addHandler(SIGTERM, [$this, 'signalHandler']);
        $config = config::getConfig(__CLASS__);
        $this->enabledWs = $config['web_sockets']['enabled'];
        $this->webSocketServerPublishEndPoint = $config['web_sockets']['web_socket_server_publish_end_point'];

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
        static $curl;

        if ($curl === null) {
            $curl = new reactCurl($this->loop);
            $curl->setSleep(0, 0, false);
            $curl->setMaxRequest(100);
        }
        $this->stdout = new Stream($this->pipes[1], $this->loop);
        $this->stdout->on('data', function ($data) use ($curl) {
            foreach ($this->stdOutLogFiles as $logFile) {
                file_put_contents($logFile, $data, FILE_APPEND);
            }
            if ($this->enabledWs) {
                foreach ($this->stdOutWsChannelIds as $channelId) {
                    $curl->prepareTask("{$this->webSocketServerPublishEndPoint}?id={$channelId}", 'POST', $data);
                    $curl->run();
                }
            }
        });
        $this->stderr = new Stream($this->pipes[2], $this->loop);
        $this->stderr->on('data', function ($data) use ($curl) {
            foreach ($this->stdErrorLogFiles as $logFile) {
                file_put_contents($logFile, $data, FILE_APPEND);
            }
            if ($this->enabledWs) {
                foreach ($this->stdErrorWsChannelIds as $channelId) {
                    $curl->prepareTask("{$this->webSocketServerPublishEndPoint}?id={$channelId}", 'POST', $data);
                    $curl->run();
                }
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
        $this->stdErrorLogFiles[$filePath] = $filePath;

        return true;
    }

    public function setStdErrorLogFiles(array $filesPaths)
    {
        $this->stdErrorLogFiles = array_combine($filesPaths, $filesPaths);

        return true;
    }

    public function removeStdErrorLogFile($filePath)
    {
        if (isset($this->stdErrorLogFiles[$filePath])) {
            unset($this->stdErrorLogFiles[$filePath]);
        }

        return true;
    }

    public function addStdOutWsChannelId($channelId)
    {
        $this->stdOutWsChannelIds[$channelId] = $channelId;

        return true;
    }

    public function setStdOutWsChannelIds(array $channelIds)
    {
        $this->stdOutWsChannelIds = array_combine($channelIds, $channelIds);

        return true;
    }

    public function removeStdOutWsChannelId($channelId)
    {
        if (isset($this->stdOutWsChannelIds[$channelId])) {
            unset($this->stdOutWsChannelIds[$channelId]);
        }

        return true;
    }

    public function addStdErrorWsChannelId($channelId)
    {
        $this->stdErrorWsChannelIds[$channelId] = $channelId;

        return true;
    }

    public function setStdErrorWsChannelIds(array $channelIds)
    {
        $this->stdErrorWsChannelIds = array_combine($channelIds, $channelIds);

        return true;
    }

    public function removeStdErrorWsChannelId($channelId)
    {
        if (isset($this->stdErrorWsChannelIds[$channelId])) {
            unset($this->stdErrorWsChannelIds[$channelId]);
        }

        return true;
    }

    protected function kill()
    {
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

    /**
     * signal handler
     *
     * @param integer $_signal
     */
    public function signalHandler($_signal = SIGTERM)
    {
        switch ($_signal) {
            case SIGTERM:
                error_log("Killing {$this->command}. Pid: {$this->pid}");
                $this->kill();
                break;
        }
    }
}