<?php


namespace langdonglei;


use Carbon\Carbon;
use think\facade\Cache;

class Throttle
{
    public static function wait($key, $limit_minute, $limit_hour = 0, $limit_day = 0, $init = 0): int
    {
        return max(
            self::check($key . ':minute', $limit_minute, 60),
            self::check($key . ':hour', $limit_hour, Carbon::now()->ceilHour()->timestamp - Carbon::now()->timestamp),
            self::check($key . ':day', $limit_day, Carbon::now()->ceilDay()->addHours($init)->timestamp - Carbon::now()->timestamp)
        );
    }

    private static function check($key, $limit, $expire)
    {
        if ($limit) {
            if (Cache::incr($key) >= $limit) {
                $wait = Cache::ttl($key);
                if ($wait < 0) {
                    $wait = 0;
                    Cache::expire($key, $expire);
                }
            }
        }
        return $wait ?? 0;
    }
}