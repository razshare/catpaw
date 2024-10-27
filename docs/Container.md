# Container

You can use `Container::get()` to get a dependency by name.

Although it is technically not required, it's good practice to name and provide your dependency using the name of an interface

```php
<?php
use CatPaw\Core\Container;
use Psr\Log\LoggerInterface;

$logger = Container::get(LoggerInterface::class)->unwrap($error);
```

You can use `Container::provide()` to provide a dependency

```php
<?php
use CatPaw\Core\Container;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;

Container::provide(HttpClient::class, static fn () => HttpClientBuilder::buildDefault());
```

You can provide a dependency as a singleton

```php
<?php
use CatPaw\Core\Container;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;

Container::provide(HttpClient::class, HttpClientBuilder::buildDefault());
```

And you can provide a dependency using the `#[Provider]` attribute

```php
<?php
use CatPaw\Core\Attributes\Provider;

#[Provider(singleton:false)]
class MyImplementation implements MyInterface {
    // ...
}
```

Your dependency will be available under both `MyImplementation` and `MyInterface` names.

Implementing an interface is not required.\
This is also allowed

```php
<?php
use CatPaw\Core\Attributes\Provider;

#[Provider(singleton:false)]
class MyImplementation {
    // ...
}
```