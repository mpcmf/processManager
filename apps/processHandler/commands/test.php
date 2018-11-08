<?php

namespace mpcmf\apps\processHandler\commands;

use mpcmf\system\application\consoleCommandBase;
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
        $process = new process('bin/mpcmf apps/processHandler/console.php childCreator', '/opt/mpcmf');
        sleep(1);

        $process->run();

        $attempts = 1;

        for (;;) {
            $status = $process->check();
            var_dump($status);
            if ($status === process::STATUS__EXITED || $status === process::STATUS__STOPPED) {
                error_log('DONE!');
                break;
            }

            sleep(1);

//            var_dump($process->getChildPids());

            if (--$attempts <= 0) {
                $process->stop();
            }
        }
    }
}