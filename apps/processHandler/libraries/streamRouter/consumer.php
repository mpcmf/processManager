<?php

namespace mpcmf\apps\processHandler\libraries\streamRouter;

use React\EventLoop\LoopInterface;

class consumer
{
    public static function factory($destination, LoopInterface $loop)
    {
        $parsed = parse_url($destination);
        if (empty($parsed['scheme'])) {
            throw new streamRouterException("Empty scheme! {$destination}");
        }

        switch ($parsed['scheme']) {
            case 'file' :
                return new fileConsumer($parsed['path']);
                break;
            case 'http' :
            case 'https' :
                return new httpConsumer($destination, $loop);
                break;
        }

        throw new streamRouterException("Unknown scheme: {$parsed['scheme']}");
    }
}