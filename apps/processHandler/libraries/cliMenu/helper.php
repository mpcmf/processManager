<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

class helper
{
    public static function formTitle($key, $value)
    {
        $titleValue = $value;
        if (is_array($value)) {
            $titleValue = json_encode($value, 448);
        } elseif ($value instanceof menuItem) {
            $titleValue = $value->getKey();
        } elseif (is_bool($value)) {
            $titleValue = $value === true ? 'true' : 'false';
        }

        return "{$key}: {$titleValue}";
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