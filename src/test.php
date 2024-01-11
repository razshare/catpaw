<?php

use CatPaw\Core\Unsafe;
use CatPaw\Schedule\Services\ScheduleService;

function sayHi():void {
    echo "Hi.";
}
function main(ScheduleService $scheduler):Unsafe {
    return $scheduler->after(due: '2 minutes', function: sayHi(...));
}