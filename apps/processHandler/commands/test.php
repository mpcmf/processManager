<?php

namespace mpcmf\apps\processHandler\commands;

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

//        $process = new process('bin/mpcmf apps/processHandler/console.php botChildCreator', '/opt/mpcmf');

        $loop = Factory::create();
        $process = new process($loop, 'bin/mpcmf apps/processHandler/console.php childCreator', '/opt/mpcmf');
        $process->addStdOutLogFile('/tmp/some_log');
        $process->run();

        $loop->addTimer(5, function () use ($process) {
           $process->addStdOutLogFile('/tmp/some_log2');
        });

        $loop->addTimer(10, function () use ($process) {
           $process->removeStdOutLogFile('/tmp/some_log2');
        });
        $loop->run();

    }
}