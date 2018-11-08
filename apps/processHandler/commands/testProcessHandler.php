<?php

namespace mpcmf\apps\processHandler\commands;

use mpcmf\apps\processHandler\libraries\processManager\config\configStorage;
use mpcmf\apps\processHandler\libraries\processManager\processHandler;
use mpcmf\apps\processHandler\libraries\processManager\server;
use mpcmf\system\application\consoleCommandBase;

use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Output\OutputInterface;


/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class testProcessHandler
    extends consoleCommandBase
{
    /**
     * Define arguments
     *
     * @return mixed
     */
    protected function defineArguments()
    {
        // TODO: Implement defineArguments() method.
    }


    protected function handle(InputInterface $input, OutputInterface $output)
    {
        $configStorage = new configStorage();
        $ph = new processHandler($configStorage);
        $server = new server($ph);
        for (;;) {
            $server->mainCycle();
            sleep(1);
        }
    }
}