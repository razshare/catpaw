<?php

use CatPaw\Queue\Services\QueueService;
use function Amp\delay;

function main(QueueService $queue): void {
    $start = time();

    $queue->queue("my-tag", function(){
        delay(5000);
        echo 'a'.PHP_EOL;
    });

    $queue->queue("my-tag", function(){
        delay(2000);
        echo 'b'.PHP_EOL;
    });

    $queue->consume();

    $finish = time();
    $delta = $finish - $start;
    echo "$delta seconds have passed.".PHP_EOL;
}