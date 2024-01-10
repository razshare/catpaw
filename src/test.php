<?php

use CatPaw\Schedule\Services\ScheduleService;
use CatPaw\Unsafe;

function sayHi():void {
    echo "Hi.";
}
function main(ScheduleService $scheduler):Unsafe {
    return $scheduler->after(due: '2 minutes', function: sayHi(...));
}