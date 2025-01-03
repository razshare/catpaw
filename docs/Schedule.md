# Schedule

You can schedule operations using `ScheduleInterface`.

# After

You can schedule functions to execute after a certain amount of time.

```php
use CatPaw\Schedule\Interfaces\ScheduleInterface;
use CatPaw\Core\Result;

function sayHi():void {
    echo 'Hi.';
}

function main(ScheduleInterface $schedule):Result {
    return $schedule->after(due:'2 minutes', function:sayHi(...));
}
```
This will schedule your function to execute in 2 minutes.

The `due` parameter must be a string in one of the following formats

| Execute after `T` time |
|------------------------|
| `T` seconds            |
| `T` minutes            |
| `T` hours              |
| `T` days               |
| `T` weeks              |
| `T` months             |
| `T` years              |

> [!NOTE]
> Replace `T` with an `integer` value.


# Every

Just like `after()`, `every()` allows you to countdown before executing the function, with the added effect that the schedule restarts whenever it ends and the callback function accepts a callable which when invoked will cancel the schedule immediately.

```php
use CatPaw\Schedule\Interfaces\ScheduleInterface;
use CatPaw\Core\Result;

function sayHi(callable $cancel):void {
    echo "Hi.";
    $cancel();  // this will cancel the schedule
}

function main(ScheduleInterface $schedule):Result {
    return $schedule->every(due:'2 minutes', function: sayHi(...));
}
```

The `due` parameter must be a string in one of the following formats

| Execute after `T` time |
|------------------------|
| `T` seconds            |
| `T` minutes            |
| `T` hours              |
| `T` days               |
| `T` weeks              |
| `T` months             |
| `T` years              |

> [!NOTE]
> Where `T` is an `integer` value.



# Daily

Schedule a function to execute daily.

```php
use CatPaw\Schedule\Interfaces\ScheduleInterface;
use CatPaw\Core\Result;

function sayHi(callable $cancel):void {
    echo "Hi.";
    $cancel();  // this will cancel the schedule
}

function main(ScheduleInterface $schedule):Result {
    return $schedule->daily(due:'at 13:00', function: sayHi(...));
}
```

Similarly to `every()`, the callback function accepts a cancel function, which you can invoke to cancel the schedule immediately.

The `due` parameter must be a string formatted as `at hh:mm:ss` or `at hh:mm:ss AM` (or _PM_).
The format `hh:mm:ss` indicates `hours:minutes:seconds`, all of which are optional.
When omitting hours, minutes or seconds, the equivalent current system value will be used instead.
