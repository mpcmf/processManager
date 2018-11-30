<?php

namespace mpcmf\apps\processHandler\commands\processHandler;

use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\api\client\native;
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
        $nativeClient = apiClient::factory();
        var_dump($nativeClient->call('process', 'getList'));

    }
}