<?php

namespace langdonglei;

use Carbon\Carbon;
use Exception;
use Redis;

class Throttle
{
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
     * @throws Exception
     */
    public static function poll(Redis $redis, string $key, array $items, int $duration = 10, int $max_requests = 1): string
    {
        # 获取轮询的值匿名函数: $key的值 是一个计数器 对目标数组取余后 得到目标数组的索引值 从而达到轮询的目的
        $poll = function ($items, $redis, $key) {
            $v = $redis->incr($key);
            if ($v == 1) {
                # 计数器初始一个过期时间
                $redis->expire($key, 86400 * 180);
            }
            return $items[$v % count($items)];
        };
        # 遍历目标数组
        $count = count($items);
        while ($count--) {
            # 从当前轮询值开始
            $item          = $poll($items, $redis, $key);
            $request_times = $redis->get("$key:$item") ?? 0;
            # 判断是否未满足限流条件 未限流 直接返回 被限流则继续下一个循环
            if ($request_times < $max_requests) {
                $request_times += 1;
                $redis->setex("$key:$item", $duration, $request_times);
                return $item;
            }
        }
        # 如果一个符合条件的都没有找出来 那就没法往后继续 只能阻断程序了
        throw new Exception('busy');
    }
}
