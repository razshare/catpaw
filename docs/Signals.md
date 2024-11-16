# Signals

A signal is a container of functions.\
When a signal is _triggered_ all listener functions will be invoked.

# Usage

- Use `Signal::create()` to create a signal
- Use `Signal::listen()` to listen for the signal trigger
- Use `Signal::send()` to trigger the signal

```php
<?php
use CatPaw\Core\Signal;
use function Amp\delay;

function main(){
    $printGreeting = Signal::create();

    // Listen for the signal.
    $printGreeting->listen(fn () => print("hello foo!\n"));
    $printGreeting->listen(fn () => print("hello bar!\n"));
    $printGreeting->listen(fn () => print("hello world!\n"));

    delay(2);

    // This will print all three strings
    // "hello foo!",
    // "hello bar!",
    // "hello world!",
    // in that order.
    $printGreeting->send();
}
```

# Clear

Use `clear()` in order to remove all listeners of the signal.

```php
<?php
use CatPaw\Core\Signal;
use function Amp\delay;

function main(){
    $printGreeting = Signal::create();

    $printGreeting->listen(fn () => print("hello foo!\n"));
    $printGreeting->listen(fn () => print("hello bar!\n"));
    $printGreeting->listen(fn () => print("hello world!\n"));

    // Remove all listeners.
    $printGreeting->clear();

    delay(2);

    // Nothing happens.
    $printGreeting->send();
}
```

# Other notes

> [!NOTE]
> - A signal cannot send values to its listeners.
> - Signal listeners cannot be removed individually by design.
>
> If you want more control of the structure and behavior, see the [stores section](./Stores.md).
