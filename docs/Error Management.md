# Error Management

Do not throw exceptions in your code, instead create results as `Result<T>` using either `ok()` or `error()`.

```php
readonly class None {}
const NONE = new None;

readonly class Result {
  private mixed $value;
  private Error $error;
}

function ok(mixed $value = NONE):Result;
function error(string|Error $message):Result;
```

# What is a result

A result is a simple object that can contain either a value or an error.

# How to create results

You can create a result by invoking `ok()` with a value
```php
use function CatPaw\Core\ok;
$result = ok("my-value");
```

or by invoking `error()` with an error message

```php
use function CatPaw\Core\error;
$result = error("My error message");
```

# Unwrap

You can check if a result contains an error by `unwrap`ping it

```php
use function CatPaw\Core\error;

$result = error("My error message");

$value = $result->unwrap($error);

if($error){
  echo "Something is wrong: $error";
} else {
  echo "OK!";
}
```

# Some advantages

The first advantage over throwing exceptions is that your control flow is linear and easier to understand because you are not required to use `try/catch` syntax.

But the most important advantage is probably type safety.\
While it is true you can check for native thrown exceptions using development tools like phpstan, psalm and so on, these tools don't guarantee your code is safely checked for errors, they are mere linting solutions.\
Using results, the php type system itself will force you to `unwrap()` the value.

> [!NOTE]
> Of course, you can choose to ignore the error after you `unwrap()` the result, but that would be a conscious decision on your part rather than mistake.
