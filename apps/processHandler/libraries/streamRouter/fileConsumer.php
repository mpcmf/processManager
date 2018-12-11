<?php

namespace mpcmf\apps\processHandler\libraries\streamRouter;

class fileConsumer
    extends consumerBase
{
    public function consume($data)
    {
        static $notWritable = [];

        if (isset($notWritable[$this->destination])) {
            if (!is_writable($this->destination)) {
                error_log("File {$this->destination} not writable!");
                return;
            }
            unset($notWritable[$this->destination]);
        }

        file_put_contents($this->destination, $data, FILE_APPEND);

        if (!is_writable($this->destination)) {
            $notWritable[$this->destination] = $this->destination;
        }
    }
}