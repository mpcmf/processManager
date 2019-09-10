<?php

namespace mpcmf\apps\processHandler\libraries\api;

use mpcmf\system\helper\cache\cache;
use mpcmf\system\helper\io\log;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class locker
{
    use cache, log;

    protected static $lockTime = 3600;
    protected static $writeLockPrefix = 'write:lock:process:';

    public static function lockWrite(array $processIds)
    {
        $items = [];
        foreach ($processIds as $id) {
            $items[self::getWriteLockKey($id)] = true;
        }

        self::cache()->setMulti($items, self::$lockTime);
    }

    public static function unLockWrite(array $processIds)
    {
        foreach ($processIds as $id) {
            self::cache()->remove(self::getWriteLockKey($id));
        }
    }

    public static function isWriteLocked($processId)
    {
        return (bool) self::cache()->get(self::getWriteLockKey($processId));
    }

    private static function getWriteLockKey($processId)
    {
        return self::$writeLockPrefix . $processId;
    }
}