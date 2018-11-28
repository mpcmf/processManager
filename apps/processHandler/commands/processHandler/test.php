<?php

namespace mpcmf\apps\processHandler\commands\processHandler;

use mpcmf\system\application\consoleCommandBase;
use React\EventLoop\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use mpcmf\apps\processHandler\libraries\processManager\process;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class test
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

    }
}