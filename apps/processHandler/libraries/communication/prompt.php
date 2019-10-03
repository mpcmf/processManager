<?php

namespace mpcmf\apps\processHandler\libraries\communication;

use Codedungeon\PHPCliColors\Color;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class prompt
{
    /** @var menu $menu */
    private $menu;

    public function __construct(menu $menu)
    {
        $this->menu = $menu;
    }

    public function completion(array $variants)
    {
        readline_completion_function(function ($input, $index) use ($variants) {
            return $variants;
        });
    }

    public function getResponse($message)
    {
        $this->menu->reDraw();

        return trim(readline($message));
    }

    /**
     * @param $message
     * @param bool $strict
     *
     * @return bool
     */
    public function getAgreement($message, $strict = true)
    {
        static $variants = [
            'strict' => [
                'yes' => true,
                'no' => false
            ],
            'not_strict' => [
                'y' => true,
                'n' => false
            ]
        ];

        $type = $strict ? 'strict' : 'not_strict';
        $message = "\n{$message}  [" . implode('/', array_keys($variants[$type])) . ']: ';

        for (;;) {
            $this->menu->reDraw();

            $answer = trim(readline($message));
            if (isset($variants[$type][$answer])) {
                return $variants[$type][$answer];
            }
        };
    }
}