# Error Management

Do not throw exceptions in your code, instead create results as `Result<T>` using either `ok()` or `error()`.

```php
readonly class None {}
const NONE = new None;

readonly class Result {
  public mixed $value;
  public Error $error;
}

function ok(mixed $value = NONE): Result;
function error(string|Error $message): Result;
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

# Convert exceptions to results

You can convert exceptions into results with `anyError()`

```php
use function CatPaw\Core\anyError;
$result = anyError(fn() => throw new Exception("Some exception message"));
```

> [!NOTE]
> The `anyError()` function will detect exceptions that are thrown, returned or yielded and convert them into results.

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

Another advantage of representing errors as values is that you can use expressions and pattern matching to manage logic

```php
use function CatPaw\Core\error;

class Error1 {}
class Error2 {}

$result = error(new Error1)

$value = $result->unwrap($error) or match($error::class){
  Error1::class => "fallback value 1",
  Error2::class => "fallback value 2",
  default       => "default value"
};
```

Note that php's match expressions is exhaustive, meaning it forces you to provide a default value.



But the most important advantage is probably type safety.\
While it is true you can check for native thrown exceptions using development tools, like phpstan, psalm and so on, these tools don't guarantee your code is safely checked for errors, they are mere linting solutions.\
Using results, the php type system itself will force you to `unwrap()` the value.

> [!NOTE]
> Of course, you can choose to ignore the error after you `unwrap()` the result, but that would be a conscious decision on your part rather than mistake.
