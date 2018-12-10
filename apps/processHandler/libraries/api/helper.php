<?php

namespace mpcmf\apps\processHandler\libraries\api;

use mpcmf\apps\processHandler\libraries\api\exceptions\notFoundParamException;
use mpcmf\apps\processHandler\libraries\api\exceptions\paramTypeValidationException;

class helper
{
    const TYPE_ARRAY = 'array';
    const TYPE_INT = 'int';
    const TYPE_STRING = 'string';
    const TYPE_BOOL = 'bool';
    const TYPE_ANY = 'any';

    public static function getParam($needle, $haystack, $type = self::TYPE_ANY, $defaultValue = 'not_specified')
    {
        if (!isset($haystack[$needle])) {
            if ($defaultValue === 'not_specified') {
                throw new notFoundParamException("Missed param: {$needle}");
            }
            return $defaultValue;
        }

        if ($type !== self::TYPE_ANY) {
            $isNeededType = false;
            if ($type === self::TYPE_STRING) {
                $isNeededType = is_string($haystack[$needle]);
            } elseif ($type === self::TYPE_INT) {
                $isNeededType = is_int($haystack[$needle]);
            } elseif ($type === self::TYPE_ARRAY) {
                $isNeededType = is_array($haystack[$needle]);
            } elseif ($type === self::TYPE_BOOL) {
                $isNeededType = is_bool($haystack[$needle]);
            }
            if (!$isNeededType) {
                throw new paramTypeValidationException("{$needle} type must be {$type}. " . gettype($haystack[$needle]) . ' given!');
            }
        }

        return $haystack[$needle];
    }

}