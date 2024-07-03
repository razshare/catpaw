# Container

You can use the dependencies container to get, set (singletons) and provide dependencies.

# Getting a dependency

You can use `Container::get()` to get a dependency by name.

Although it is technically not required, it's good practice to name and provide your dependency using an interface.

```php
<?php
use CatPaw\Core\Container;
use Psr\Log\LoggerInterface;

$logger = Container::get(LoggerInterface::class)->unwrap($error);
```


# Providing a dependency

You can use `Container::provide()` to provide a dependency.

```php
<?php
use CatPaw\Core\Container;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;

Container::provide(HttpClient::class, static fn () => HttpClientBuilder::buildDefault());
```

You can also provide your dependency as a singleton, all you have to do is pass in the object directly.

```php
<?php
use CatPaw\Core\Container;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;

Container::provide(HttpClient::class, HttpClientBuilder::buildDefault());
```

# Providing a dependency using attributes

You can also provide a dependency by adding the `#[Provider]` attribute to your class, like so

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

Although in this case, your dependency will only be available under the name `MyImplementation`, so third parties would need to be aware of the `MyImplementation` class in order use or compose it.

Whereas using shared interfaces will normally make it easier to swap dependencies.\
For that reason it is recommended to implement dependencies using interfaces.

> [!NOTE]
> Setting the `singleton` property to `true` will make it so that your dependency will be cached as a singleton.

> [!NOTE]
> The `singleton` property defaults to `true`.