<?php


use langdonglei\Throttle;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertTrue;

class ThrottleTest extends TestCase
{
    public function testPoll()
    {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1');
            $item = (new Throttle($redis))->poll([
                1,
                2,
                3
            ], 10);
            var_dump($item);

        } catch (Throwable $e) {
            var_dump($e->getMessage());
            assertTrue(false);
        }
        assertTrue(true);
    }

    public function testWait()
    {
        $redis = new Redis();
        $redis->connect('127.0.0.1');
        var_dump(Throttle::wait($redis, 'test:1', 3));
    }
}
