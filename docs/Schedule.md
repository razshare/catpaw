# Schedule

You can schedule operations using _ScheduleService_.

## after()

You can schedule functions to execute after a certain amount of time.

```php
<?php
use CatPaw\Schedule\Services\ScheduleService;
use CatPaw\Core\Result;

function sayHi(): void {
    echo 'Hi.';
}

function main(ScheduleService $schedule): Result {
    return $schedule->after(due:'2 minutes', function:sayHi(...));
}
```
This will schedule your function to execute in 2 minutes.

The _due_ parameter must be a string in one of the following formats

| Execute after _T_ time |
|------------------------|
| _T_ seconds            |
| _T_ minutes            |
| _T_ hours              |
| _T_ days               |
| _T_ weeks              |
| _T_ months             |
| _T_ years              |

> [!NOTE]
> Replace _T_ with an _integer_ value.


## every()

Just like _after()_, _every()_ allows you to countdown before executing the function, with the added effect that the schedule restarts whenever it ends and the callback function accepts a callable which when invoked will cancel the schedule immediately.

```php
<?php
use CatPaw\Schedule\Services\ScheduleService;
use CatPaw\Core\Result;

function sayHi(callable $cancel): void {
    echo "Hi.";
    $cancel();  // this will cancel the schedule
}

function main(ScheduleService $schedule): Result {
    return $schedule->every(due:'2 minutes', function: sayHi(...));
}
```

The _due_ parameter must be a string in one of the following formats

| Execute after _T_ time |
|------------------------|
| _T_ seconds            |
| _T_ minutes            |
| _T_ hours              |
| _T_ days               |
| _T_ weeks              |
| _T_ months             |
| _T_ years              |

> [!NOTE]
> Where _T_ is an _integer_ value.



## daily()

Schedule a function to execute daily.

```php
<?php
use CatPaw\Schedule\Services\ScheduleService;
use CatPaw\Core\Result;

function sayHi(callable $cancel): void {
    echo "Hi.";
    $cancel();  // this will cancel the schedule
}

function main(ScheduleService $schedule): Result {
    return $schedule->daily(due:'at 13:00', function: sayHi(...));
}
```

Similarly to _every()_, the callback function accepts a cancel function, which you can invoke to cancel the schedule immediately.

The _due_ parameter must be a string formatted as `at hh:mm:ss` or `at hh:mm:ss AM` (or _PM_).
The format `hh:mm:ss` indicates `hours:minutes:seconds`, all of which are optional.
When omitting hours, minutes or seconds, the equivalent current system value will be used instead.
