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
    }

    protected function handle(InputInterface $input, OutputInterface $output)
    {
        $hostname = $input->getOption('hostname');

        $loop = Factory::create();
        $configStorage = new configStorage();
        $ph = new processHandler($configStorage, $loop);
        if ($hostname) {
            $ph->getServer()->setHostName($hostname);
        }
        $ph->start();

        $loop->run();
    }
}