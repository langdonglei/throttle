<?php

namespace langdonglei;

use Carbon\Carbon;
use Exception;
use Redis;
use Throwable;

class Throttle
{
    private Redis $redis;

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    public static function wait(Redis $redis, string $key, int $limit_minute, int $limit_hour = 0, int $limit_day = 0, int $reset_day_at = 0): int
    {
        $wait = function ($key, $limit, $expire) use ($redis) {
            if ($limit) {
                if ($redis->incr($key) > $limit) {
                    $wait = $redis->ttl($key);
                    if ($wait < 0) {
                        $wait = $expire;
                        $redis->expire($key, $expire);
                    }
                }
            }
            return $wait ?? 0;
        };
        return max(
            $wait($key . ':minute', $limit_minute, 60),
            $wait($key . ':hour', $limit_hour, Carbon::now()->ceilHour()->timestamp - Carbon::now()->timestamp),
            $wait($key . ':day', $limit_day, Carbon::now()->ceilDay()->addHours($reset_day_at)->timestamp - Carbon::now()->timestamp)
        );
    }

    /**
     * @throws Throwable
     */
    public function poll($items, $interval = 1, $interval_times = 1, $counter = 'counter'): string
    {
        $ttl = $_ENV['THROTTLE_LOG_TTL'] ?? 86400 * 180;
        $i   = count($items);
        while ($i--) {
            $kc   = "$counter:all";
            $vc   = $this->inc($kc, $ttl);
            $item = $items[$vc % count($items)];
            $ki   = "$counter:$item";
            $vi   = $this->inc($ki, $interval);
            if ($vi <= $interval_times) {
                $this->inc("$counter:valid", $ttl);
                return $item;
            }
        }
        throw new Exception('busy');
    }

    private function inc($key, $ttl): int
    {
        $value = $this->redis->incr($key);
        if ($this->redis->ttl($key) == -1) {
            $this->redis->expire($key, $ttl);
        }
        return $value;
    }
}
