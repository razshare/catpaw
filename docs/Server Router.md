> [!NOTE]
> _Attributes used in this document_
> - `#[Produces]` - _supported by the open api service_ ✅
> - `#[Consumes]` - _supported by the open api service_ ✅
> - `#[Param]` - _supported by the open api service_ ✅

# Router

Routes are defined through route handlers.

Route handlers are functions that trigger whenever a user requests a specific resource.\
You can map different handlers to different http paths and methods.

Before adding any route handlers you'll need to start the web server.

```php
<?php
use CatPaw\Web\Interfaces\ServerInterface;
use function CatPaw\Core\anyError;
use CatPaw\Core\Unsafe;

function main(ServerInterface $server): Unsafe {
    return $server->start();
}
```

## GET

You can create routes through
```php
$router->get('/path', handler(...));
```

Here's a complete example

```php
<?php
use CatPaw\Web\Interfaces\ServerInterface;
use CatPaw\Web\Interfaces\RouterInterface;
use function CatPaw\Core\anyError;
use function CatPaw\Core\success;
use CatPaw\Core\Unsafe;

function handler() {
    return success('there are no cats here');
}

function main(ServerInterface $server, RouterInterface $router): Unsafe {
    return anyError(function() use($server, $router) {
        $router->get('/cats', handler(...))->try();
        $server->start()->try();
    });
}
```

This creates a _GET /cats_ route which responds with _"there are no cats here"_.

> [!NOTE]
> All paths _MUST_ start with `/`.

## POST

Similarly to the _GET_ example, the following

```php
$router->post('/cats', handler(...))
```
will create a **POST** route.

A more complete example would be

```php
<?php
use CatPaw\Web\Interfaces\ServerInterface;
use CatPaw\Web\Interfaces\RouterInterface;
use function CatPaw\Core\anyError;
use CatPaw\Web\Attributes\Consumes;
use CatPaw\Web\Attributes\Produces;
use CatPaw\Core\Unsafe;
use CatPaw\Web\Body;

#[Consumes(
    contentType: 'text/plain',
    className: 'string',
    example: 'hello world'
)]
#[Produces(
    status: 200,
    contentType: 'text/plain',
    description: 'On success',
    className: 'string',
)]
function handler(Body $body){
    echo "Received body: {$body->asText()}\n";
}

function main(ServerInterface $server, RouterInterface $router): Unsafe {
    return anyError(function() use($server, $router) {
        $router->post('/cats', handler(...))->try();
        $server->start()->try();
    });
}
```


> [!NOTE]
> As a bonus, these route definitions will also generate an openapi documentation.\
> Read more about this feature in the [openapi document](./18.open-api.md).

# Filesystem routes

You can automate routes definitions by describing them through the filesystem instead of programmatically defining them.

Create a new _src/api_ directory
```shell
mkdir -p src/api
```
Scan the directory using `::withApiLocation()`
```php
$server->withApiLocation('src/api');
```
> [!NOTE]
> You can add a prefix to all your api routes using `::withApiPrefix()`.
> ```php
> $server->withApiLocation('src/api')->withApiPrefix('api');
> ```

Create a file _src/api/get.php_, this file should return a function.

```php
<?php
use function CatPaw\Core\success;
return function(){
    return success("hello world");
};
```

This file will serve requests for _GET /_.

> [!NOTE]
> The route handler function can inject any of the dependencies
> that a normal route handler would, including path parameters,
> sessions, produced and consumed content etc.\
> That is because, under the hood, the resulting function is actually
> used to create a normal route handler, which means that the
> OpenAPI service will detect these routes and automatically document
> them for you.\
> \
> The file name should always be _METHOD.php_ where _METHOD_ is an http method.\
> This way the scanner is able to infer the http method to use
> when exposing the function you're returning.\
> \
> The route path will match the location of your file relative to _src/api_, in this case _/_.
>
> This means that, in this case, the scanner will automatically pick up the function and use it to map a route handler
> for _GET /_, which when called will return the string _hello world_ as _text/plain_.

The equivalent programmatic route definition is

```php
$router->get('/', fn () => 'hello world');
```

### Path parameters

Create a new file _src/api/about/{username}/get.php_

```sh
mkdir -p src/api/about/{username}/get.php
```

Path parameters are automatically linked to function parameters when their names match, so in this case you can inject **$username**.
```php
<?php
use function CatPaw\Core\success;
return function(string $username) {
    return success("this is $username's about page.");
}
```

You can further customize your path parameters using _#[Param]_.
```php
<?php
use CatPaw\Web\Attributes\Param;

return function(#[Param('\w{3,15}')] string $username) {
  return success("this is $username's about page.");
};
```
In this example **$username** must be at least 3 and at most 15 characters long.\
If the criteria is not met, then the server will skip the route handler and potentially respond with
a _404 Not Found_.

> [!NOTE]
> You can read more about path parameters [here](./2.path-parameters.md).

The equivalent programmatic definition is

```php
$router->get('/about/{username}', handler(...));
```
