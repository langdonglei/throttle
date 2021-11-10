<?php


use langdonglei\Throttle;
use PHPUnit\Framework\TestCase;

class ThrottleTest extends TestCase
{
    public function testPoll()
    {
        $redis = new Redis();
        $redis->connect('127.0.0.1');
        $redis->auth('foobared');
        var_dump(Throttle::poll($redis, 'test:2', [
            'a',
            'b',
            'c',
            'd',
            'e',
            'f',
            'g'
        ]));
    }

    public function testWait()
    {
        $redis = new Redis();
        $redis->connect('127.0.0.1');
        $redis->auth('foobared');
        var_dump(Throttle::wait($redis, 'test:1', 3));
    }
}
