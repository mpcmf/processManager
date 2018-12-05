<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

class helper
{
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