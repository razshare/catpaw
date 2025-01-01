# Stores

Stores are observable objects that contain a value.

Each store has a `subscribe()` method which allows the execution of a _callable_ whenever the value of the store changes, this callable provides the new value of the store as a parameter.

Stores can be of 2 types

- Writable stores
- Readable stores


# Writable

You can read and write values in a writable store.

You can set the value on creation or some time after by using the `set()` method.

```php
use function CatPaw\Store\Writable;

function main() {
    $store = new Writable("A");       // set on creation
    echo $store->get().PHP_EOL;           // will print "A"
    $store->set("B");                     // changing the value
    echo $store->get().PHP_EOL;           // will print "B"
}
```

Use `subscribe()` to watch over the value of the store.
> [!NOTE]
> Remember to `$unsubscribe()` from the store after you're done with it.

```php
use function CatPaw\Store\Writable;

function main() {
    $store       = new Writable("A");
    $unsubscribe = $store->subscribe(static function($value) {
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

### Output

```
A
B
```

Notice how it doesn't print `C`; that is because by the time `C` is set, `$unsubscribe()` has already been invoked.

# Readable

You can only read values from a readable store.

```php
use function CatPaw\Store\Readable;

function main() {
    $store = new Readable("default value", static function($set) {
        $set("hello world");
    });

    
    $unsubscribe = $store->subscribe(static function($value){
        echo $value.PHP_EOL;
    })

    $unsubscribe();
}
```

### Output

```sh
default value
hello world
```
