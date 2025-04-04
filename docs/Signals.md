# Signals

A signal is an object that can `send` hints to its `listen`ers.

```php
use CatPaw\Core\Signal;
use function Amp\delay;

function main(){
    $printGreeting = new Signal();

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
use CatPaw\Core\Signal;
use function Amp\delay;

function main(){
    $printGreeting = new Signal();

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
