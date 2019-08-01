<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

use Codedungeon\PHPCliColors\Color;

class helper
{
    public static function formTitle($key, $value)
    {
        $titleValue = $value;
        if (is_array($value)) {
            $titleValue = json_encode($value, 448);
        } elseif (is_bool($value)) {
            $titleValue = $value === true ? 'true' : 'false';
        }

        return "{$key} : {$titleValue}";
    }

    public static function formProcessTitle(array $processData, array $serverData)
    {
        $stateColor = Color::GREEN;
        if ($processData['state'] === 'stop' || $processData['state'] === 'stopped') {
            $stateColor = Color::RED;
        }
        $state = $stateColor . " {$processData['state']}" . Color::RESET;

        return self::padding($processData['name'], self::padding($state, $serverData['host'], 20), 100);
    }

    public static function padding($label, $result, $length = 20, $delimiter = ' ')
    {
        $labelLength = mb_strlen($label);
        if ($labelLength >= $length) {
            return "{$label}{$delimiter}{$result}";
        }

        $countDelimitersToComplete = $length - $labelLength;
        do {
            $label .= $delimiter;
        } while (--$countDelimitersToComplete);

        return "{$label}{$result}";
    }
}