<?php

namespace mpcmf\apps\processHandler\commands;

use mpcmf\system\application\consoleCommandBase;
use mpcmf\system\threads\thread;
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

        $thread = new thread([$this, 'dummyThread']);
        $thread->start();
        $threadPool[] = $thread;

//        $thread = new thread([$this, 'threadWithThread']);
//        $thread->start();
//        $threadPool[] = $thread;

        /** @var thread $thread */
        while (count($threadPool) > 0) {
            foreach ($threadPool as $id => $thread) {
                if (!$thread->isAlive()) {
                    $thread->kill();
                    unset($threadPool[$id]);
                }
            }
            usleep(10000);
        }
    }

    public function dummyThread()
    {
//        error_log('DT' . posix_getpid());
        $attempts = 100;
        do {
            for ($i = 0; $i<10000000; $i++) {}
            error_log($i);
            var_dump($i);
        } while (--$attempts > 0);
    }

    public function threadWithThread()
    {
//        error_log('MT' . posix_getpid());
        $thread = new thread([$this, 'dummyThread']);
        $thread->start();

        while ($thread->isAlive()) {
            usleep(10000);
        }

        $thread->kill();
    }

    public function __destruct()
    {
//        error_log('DESTRUCT!!!');
    }
}