# Goffi

Invoke _Go_ functions from _Php_.

# Usage

> [!NOTE]
> You will need [Go](https://go.dev/) and C++ installed on your machine.\
> Refer to the [official guide in order to install Go](https://go.dev/doc/install).\
> You can install the C++ requirements with
> ```sh
> apt install build-essential
> ```

Write your _Go_ program, for example in a `lib.go` file.

```go
// ./lib.go
package main
import "C"
func main() {}

//export DoubleIt
func DoubleIt(x int) int {
    return x * 2
}
```

The `//export DoubleIt` annotation will make it so that the function `DoubleIt()` will be exposed.

Compile your program to a shared object
```sh
php catpaw.phar -g lib.go
```
This will create 2 files, your shared object `lib.so` and its C header file `lib.static.h`.

Now use `GoInterface::load()` to interop with your _Go_ program from _Php_.

```php
<?php
// src/main.php
use CatPaw\Core\Unsafe;
use CatPaw\Go\Interfaces\GoInterface;
use function CatPaw\Core\anyError;

interface Contract {
    /**
     * Double a value.
     */
    function DoubleIt(int $value):int;
}

function main(GoInterface $go):Unsafe{
    return anyError(function() use($go) {
        $lib     = $go->load(Contract::class, './lib.so')->try();
        $doubled = $lib->DoubleIt(3);
        echo "doubled: $doubled\n";
    });
}
```

> [!NOTE]
> If any of your interface methods doesn't specify a return type, the `GoInterface::load()` call will fail.

Run the program.

```sh
composer prod:start
```

It should print

```sh
doubled: 6
```

# Usage with strings

Given the following Go program

```go
package main

import "C"

func main() {}

//export Greeting
func Greeting(name *C.char) *C.char {
    return C.CString("hello " + C.GoString(name))
}
```
Call _Greeting_ from php like so

```php
<?php
use CatPaw\Core\Unsafe;
use CatPaw\Go\Interfaces\GoInterface;
use function CatPaw\Core\anyError;

interface Contract {
    function Greeting(string $name):string;
}

function main(GoInterface $go):Unsafe {
    return anyError(function() use($go) {
        $goffi = $go->load(Contract::class, './lib.so')->try();
        echo $goffi->Greeting('world').PHP_EOL;
    });
}
```

Run it with
```sh
composer prod:start
```
it should print `hello world` to the terminal.


# Other notes

More quality of life improvements will come in the future.