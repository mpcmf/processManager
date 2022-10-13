<?php

namespace mpcmf\apps\processHandler\commands\processHandler;

use mpcmf\apps\processHandler\libraries\processManager\config\configStorage;
use mpcmf\apps\processHandler\libraries\processManager\processHandler;
use mpcmf\system\application\consoleCommandBase;
use React\EventLoop\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Ildar Saitkulov <saitkulovim@gmail.com>
 */
class runProcessHandler
    extends consoleCommandBase
{
    /**
     * Define arguments
     *
     * @return mixed
     */
    protected function defineArguments()
    {
        $this->addOption('hostname', null, InputOption::VALUE_OPTIONAL, 'Run process handler with hostname');
        $this->addOption('child_memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit for child processes in Mb');
    }

    protected function handle(InputInterface $input, OutputInterface $output)
    {
        $hostname = $input->getOption('hostname');
        $childMemoryLimit = (int) $input->getOption('child_memory_limit');

        $loop = Factory::create();
        $configStorage = new configStorage();
        $ph = new processHandler($configStorage, $loop);

        if ($childMemoryLimit > 0) {
            self::log()->addNotice("Enabled children memory control, max allowed memory [{$childMemoryLimit} Mb].");

            $memoryHandler = function($pid) use ($childMemoryLimit) {
                $this->notifyMemoryConsumingProcesses($pid, $childMemoryLimit, 30, SIGUSR1);
            };
            $memoryHandlerGetArgs = static function() {return getmypid();};

            $ph->registerHandler('memory_limit_notifier', $memoryHandler, $memoryHandlerGetArgs);
        }
        if ($hostname) {
            $ph->getServer()->setHostName($hostname);
        }
        $ph->start();

        $loop->run();
    }

    protected function notifyMemoryConsumingProcesses(int $protectedPid, int $memoryLimit, int $procCount, int $signal): void
    {
        $memConsumingPids = $this->getTopMemoryConsumingProcesses($procCount, $protectedPid);
        foreach ($memConsumingPids as $pid) {
            $pid = (int) $pid;
            $procMemory = $this->getProcessMemoryByPidInMB($pid);
            if ($procMemory < $memoryLimit) {
                continue;
            }

            posix_kill($pid, $signal);
        }
    }

    protected function getProcessMemoryByPidInMB(int $pid): float
    {
        $smapsFileName = "/proc/{$pid}/smaps";
        if (!file_exists($smapsFileName)) {
            return 0;
        }

        $getProcessMemory = "awk '/Pss:/{ sum += $2 } END { print sum/1024 }' ";

        return (float) trim(shell_exec($getProcessMemory));
    }

    protected function getTopMemoryConsumingProcesses(int $top, int $protectedPid = 0): array
    {
        $command = "ps --forest -o pid,pmem,cmd -g $(ps -o sid= -p {$protectedPid}) | grep child_thread | sort -rn -k 2 | head -{$top} | awk '{print $1}'";
        $topPids = shell_exec($command);

        return explode(PHP_EOL, trim($topPids));
    }
}