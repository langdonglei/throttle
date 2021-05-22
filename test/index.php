<?php

use Carbon\Carbon;
use langdonglei\Throttle;

include 'bootstrap.php';

$a = Throttle::wait('i', 1, 4, 8, 9);

var_dump($a);
var_dump(Carbon::now()->ceilHour()->timestamp - Carbon::now()->timestamp);
var_dump(Carbon::now()->ceilDay()->addHours(9)->timestamp - Carbon::now()->timestamp);
