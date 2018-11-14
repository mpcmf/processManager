<?php

namespace mpcmf\apps\processHandler\commands;

use mpcmf\system\application\consoleCommandBase;
use mpcmf\system\threads\thread;
use mpcmf\system\threads\threadPool;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class childCreator
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
        $tp = new threadPool();
        $tp->setMaxQueue(0);
        $tp->setMaxThreads(2);

        while ($tp->refresh()) {
            if ($tp->getPoolCount() < $tp->getMaxThreads()) {
                self::log()->addInfo('Starting worker...');
                $tp->add([$this, 'dummyThread']);
            }
            usleep(100);
        }
    }

    public function dummyThread()
    {
        //        error_log('DT' . posix_getpid());
        $tp = new threadPool();
        $tp->setMaxQueue(0);
        $tp->setMaxThreads(10);

        while ($tp->refresh()) {
            if ($tp->getPoolCount() < $tp->getMaxThreads()) {
                self::log()->addInfo('Starting worker...');
                $tp->add([$this, 'testSleep']);
            }
            usleep(100);
        }
    }

    public function testSleep()
    {
        sleep(1000);
    }
}