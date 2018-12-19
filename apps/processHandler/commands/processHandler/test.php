<?php

namespace mpcmf\apps\processHandler\commands\processHandler;

use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\api\client\native;
use mpcmf\apps\processHandler\libraries\cliMenu\itemFilter;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\cliMenu\selectAllControlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\terminal;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\processEditControlItem;
use mpcmf\apps\processHandler\libraries\processManagerCliMenu\processNewControllerItem;
use mpcmf\apps\processHandler\libraries\streamRouter\reactStream;
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
    protected $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
        //        0 => ['file', 'php://stdin', 'r'],
        //        1 => ['file', 'php://stdout', 'a'],
        //        2 => ['file', 'php://stderr', 'a']
    ];

    protected $pipes = [];

    protected $processDescriptor;
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

        $loop = Factory::create();
        $this->processDescriptor = proc_open('ping google.com', $this->descriptors, $this->pipes, '/tmp/');

        $loop->addPeriodicTimer(1, function () {
            $processStatus = proc_get_status($this->processDescriptor);
            var_dump("running: {$processStatus['running']}");
        });

        /*stream_set_blocking($this->pipes[1], 0);

        $loop->addPeriodicTimer(1, function () {
            var_dump(stream_get_contents($this->pipes[1], 65536));
        });*/


        $stream = new reactStream($this->pipes[1], $loop);
        $stream->on('data', function ($data) {
            var_dump($data);
        });

        $loop->run();
    }
}