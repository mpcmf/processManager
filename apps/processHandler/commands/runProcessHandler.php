<?php

namespace mpcmf\apps\processHandler\commands;

use mpcmf\apps\processHandler\libraries\processManager\config\configStorage;
use mpcmf\apps\processHandler\libraries\processManager\processHandler;
use mpcmf\system\application\consoleCommandBase;

use React\EventLoop\Factory;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Output\OutputInterface;


/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
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

    }


    protected function handle(InputInterface $input, OutputInterface $output)
    {
        $loop = Factory::create();

        $configStorage = new configStorage();
        $ph = new processHandler($configStorage, $loop);
        $ph->start();

        $loop->run();
    }
}