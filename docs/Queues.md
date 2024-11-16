# Queues

A queue is a sequence of _callables_ that will always execute in a FIFO (First In First Out) order.

Each queue is identified by a unique tag, a name.

Queues can overlap while executing, but callables within the queues themselves will never overlap.

The `QueueService` provides 2 methods, `queue()` and `consume()`

```php
/**
 * Queue a callable to run on a specific queue.
 * @param  string                   $tag the name of the queue.
 *                                  If it doesn't exist it will be created automatically.
 *
 * @param  callable                 $action callable to execute
 * @throws InvalidArgumentException
 * @return void
 */
public function queue($tag, $action);
```
```php
/**
 * Consume all available callables on all queues.
 * Will not execute a second time unless the previous execution has finished.
 * @return void
 */
public function consume();
```

## Tagging

Two callables that are delayed by 5 and 2 seconds using different tags _tag-a_ and _tag-b_.

 ```php
use CatPaw\Queue\Services\QueueService;
use function Amp\delay;

function main(QueueService $queue):void {
    $start = time();

    $queue->queue("tag-a", function() {
        delay(5000);
        echo 'a'.PHP_EOL;
    });

    $queue->queue("tag-b", function() {
        delay(2000);
        echo 'b'.PHP_EOL;
    });

    $queue->consume();

    $finish = time();
    $delta  = $finish - $start;
    echo "$delta seconds have passed.".PHP_EOL;
}
 ```

Output
```bash
b
a
5 seconds have passed.
```

## Tagging the same identifier twice

Two callables that are delayed by 5 and 2 seconds both using the same tag _my-tag_.

```php
use CatPaw\Queue\Services\QueueService;
use function Amp\delay;

function main(QueueService $queue):void {
    $start = time();

    $queue->queue("my-tag", function() {
        delay(5000);
        echo 'a'.PHP_EOL;
    });

    $queue->queue("my-tag", function() {
        delay(2000);
        echo 'b'.PHP_EOL;
    });

    $queue->consume();

    $finish = time();
    $delta  = $finish - $start;
    echo "$delta seconds have passed.".PHP_EOL;
}
```

Output
```bash
a
b
7 seconds have passed.
```
