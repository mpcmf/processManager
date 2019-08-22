<?php

namespace mpcmf\apps\processHandler\libraries\communication;

use Codedungeon\PHPCliColors\Color;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class operationResult
{
    public static function notify($success, array $errors)
    {
        if ($success) {
            echo "\n" . Color::GREEN . "Operation completed successfully \n" . Color::RESET;
        } else {
            echo "\n" . Color::RED . "Operation failed \n" . Color::RESET;
            foreach ($errors as $error) {
                echo "\t - " . Color::YELLOW . $error . Color::RESET;
            }
        }

        sleep(3);
    }
}