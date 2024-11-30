# Stores

Stores are observable objects that contain a value.

Each store has a `subscribe()` method which allows the execution of a _callable_ whenever the value of the store changes, this callable provides the new value of the store as a parameter.

Stores can be of 2 types:

- Readable stores
- Writable stores


Both stores implement the `subscribe()` and `get()` methods, but only the `Writable` store implements the `set()` method.

# Writable

A writable store's value can be set on creation or some time after by using the `set()` method.

```php
use function CatPaw\Store\writable;

function main() {
    $store = writable("A");       //set on creation
    echo $store->get().PHP_EOL;   //will print "A"
    $store->set("B");             //changing the value
    echo $store->get().PHP_EOL;   //will print "B"
}
```

 Use `->subscribe()` to watch over the value of the store.\
 The `->subscribe()` method will return itself a function.\
 You can use this function to cancel the store subscription.

```php
use function CatPaw\Store\writable;

function main() {
    $store       = writable("A");
    $unsubscribe = $store->subscribe(function($value) {
        echo $value.PHP_EOL;
    });
    $store->set("B");

    $unsubscribe();
    // Unsubscribing here means the
    // following `$store->set("C")` call
    // will not trigger any subscribers.

    $store->set("C");
}
```
The above code will print
```
A
B
```
to the console.<br/>
It won't print `C` because by the time `C` is set, `$unsubscribe()` has already been invoked.

# Readable

```php
namespace CatPaw\Store;
/**
 * @param  mixed         $value initial value of the store
 * @param  false|Closure $start
 * @return Readable
 */
function readable($value, $start = false);
```

A readable store is very similar to a writable store.<br/>
Two things differentiate a readable store from a writable one:

1. A readable store does not offer a public `set()` method.
2. A readable store requires a start _callable_ when created, this _callable_ will be invoked when the first subscriber subscribes to the store.<br/>
   The start _callable_ takes  _1 parameter_ and *should* _return a function_.<br/>
      - The _parameter_ is a `$set` function which can be used to set the store's value.<br/>
      - The _function_ it returns is a cleanup function.\
        It will be invoked when there are no subscribers left.

```php
use function CatPaw\Store\readable;
use function CatPaw\Store\writable;

function main() {
    $unsubscribers = [];

    $counter = writable(0);

    $unsubscribeAll = function() use (&$unsubscribers) {
        foreach ($unsubscribers as $unsubscribe) {
            $unsubscribe();
        }
    };

    $store = readable("default", function($set) {
        $set("hello world");
        return function() {
            echo "All subscribers have unsubscribed\n";
        };
    });

    $unsubscribers[] = $store->subscribe(function($value) use (&$counter) {
        $counter->set($counter->get() + 1);
        echo "new value received: $value".PHP_EOL;
    });

    $unsubscribers[] = $store->subscribe(function($value) use (&$counter) {
        $counter->set($counter->get() + 1);
        echo "new value received: $value".PHP_EOL;
    });

    $unsubscribers[] = $store->subscribe(function($value) use (&$counter) {
        $counter->set($counter->get() + 1);
        echo "new value received: $value".PHP_EOL;
    });

    $counter->subscribe(function(int $counter) use($unsubscribeAll) {
        if($counter < 6){
            return;
        }
        $unsubscribeAll();
    });
}
```

this code will output

```sh
new value received: default
new value received: default
new value received: default
new value received: hello world
new value received: hello world
new value received: hello world
All subscribers have unsubscribed
```
