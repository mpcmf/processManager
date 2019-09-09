<?php

namespace mpcmf\apps\processHandler\libraries\api;

use mpcmf\system\helper\cache\cache;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class locker
{
    use cache;

    protected static $lockTime = 3600;
    protected static $writeLockPrefix = 'write:lock:process:';

    public static function lockWrite(array $processIds)
    {
        foreach ($processIds as $id) {
            $key = self::getWriteLockKey($id);
            error_log("Locking {$key}");sleep(2);
            self::cache()->set($key, true, self::$lockTime);
        }
    }

    public static function unLockWrite(array $processIds)
    {
        foreach ($processIds as $id) {
            $key = self::getWriteLockKey($id);
            error_log("Unlocking {$key}");sleep(2);

            self::cache()->remove($key);
        }
    }

    public static function isWriteLocked($processId)
    {
        $key = self::getWriteLockKey($processId);
        error_log("Is write locked {$key}");sleep(2);
        return (bool) self::cache()->get($key);
    }

    private static function getWriteLockKey($processId)
    {
        return self::$writeLockPrefix . $processId;
    }
}