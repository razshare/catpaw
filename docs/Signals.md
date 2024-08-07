# Signals

A signal is a container of functions.\
When a signal is _triggered_ all listener functions will be invoked.

## Usage

- Use _Signal::create()_ to create a signal
- Use _Signal::listen()_ to listen for the signal
- Use _Signal::send()_ to trigger the signal

```php
<?php
use CatPaw\Core\Dignal;
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

## Clear

Use _clear()_ in order to remove all listeners of the signal.

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

## More advanced usage

The signal mechanism in itself is actually very simple, it's nothing more than a list of functions that will be invoked on _send()_.

The signal api is simple by design, because although a single signal by itself is very simple to understand, complexity will increase as the number of signals used increases.

For example you can create _apis_ that receive signals and act on them, while the signals themselves could be sent asynchronously.

```php
<?php
use CatPaw\Core\Signal;
use function Amp\async;
use function Amp\delay;
use function CatPaw\Core\out;
use function CatPaw\Core\execute;
use function CatPaw\Core\anyError;

function main(){
    return anyError(function(){
        $kill = Signal::create();

        async(function() use($kill) {
            delay(2);
            $kill->send();
        });

        execute(
            command:'git push',
            output:out(),
            workDirectory:'.',
            kill:$kill,
        )->try();
    });
}
```

In this example the program tries to run a `git push`.

The _execute()_ function accepts a _kill_ signal, and is internally listening to it, so that when it triggers, the `git push` command is killed.

Effectively it's a way of implementing a timeout of two seconds.

You can also use signals to synchronize functions that are being invoked in completely unrelated environments.

```php
<?php
use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Attributes\Service;
use CatPaw\Core\Signal;

#[Service]
class UserSignedInService {
    public readonly Signal $signedIn;
    private function __construct() {
        $this->signedIn = Signal::create();
    }

    #[Entry] function start() {
        // Logic to detect a user has signed in
        // This logic should invoke `$this->signedIn->send()` at some point.
    }
}

#[Service]
class UserSignedOffService {
    public readonly Signal $signedOff;
    private function __construct() {
        $this->signedOff = Signal::create();
    }

    #[Entry] function start() {
        // Logic to detect a user has signed off.
        // This logic should invoke `$this->signedOff->send()` at some point.
    }
}

function main(
    UserSignedInService $userSignedInService,
    UserSignedOffService $userSignedOffService,
){
    $counter = 0;

    $userSignedInService->signedIn->listen(function() use(&$counter) {
        $counter++;
    });

    $userSignedOffService->signedOff->listen(function() use(&$counter) {
        if($counter === 0){
            return;
        }
        $counter--;
    });
}
```

In this example I'm detecting users signing in and off and tracking the number of online users.

Obviously this is a proof of concept, I'm not implementing the actual logic of detecting when a user signs in and off.

## Other notes

> [!NOTE]
> - A signal cannot send values to its listeners.
> - Signal listeners cannot be removed individually by design.
>
> If you want more control of the structure and behavior, see the [stores section](./Stores.md).
