# Router

Routes are defined through __route handlers__, which are functions that trigger whenever a client hits a specific http endpoint.

# Routes

You can map a route handler using `$router->addHandler()`

```php
use CatPaw\Web\Interfaces\ServerInterface;
use CatPaw\Web\Interfaces\RouterInterface;

function handler() {
    return "there are no cats here";
}

function main(
    ServerInterface $server, 
    RouterInterface $router,
):void {
    $router->addHandler("GET", "/cats", handler(...))->unwrap($error);

    if ($error) {
        die($error)
    }

    $server->start()->unwrap($error);

    if ($error) {
        die($error)
    }
}
```

This creates a _GET /cats_ route which responds with _"there are no cats here"_.

> [!NOTE]
> All paths _MUST_ start with `/`.


Similarly to the _GET_ example, `$router->addHandler("POST", ...)` will map a **POST** route

```php
use CatPaw\Web\Interfaces\ServerInterface;
use CatPaw\Web\Interfaces\RouterInterface;
use CatPaw\Web\Body;

function handler(Body $body){
    return "Received body: {$body->text()}\n";
}

function main(
    ServerInterface $server, 
    RouterInterface $router,
):void {
    $router->addHandler("POST", "/cats", handler(...))->unwrap($error);

    if ($error) {
        die($error)
    }

    $server->start()->unwrap($error);

    if ($error) {
        die($error)
    }
}
```

# Filesystem Routes

You can automate routes definitions by describing them through the filesystem.

Create a new _src/api_ directory
```shell
mkdir -p src/api
```
Scan the directory using `$server->withApiLocation()`
```php
function main(ServerInterface $server):void {
    $server
        ->withApiLocation("src/api")
        ->withApiPrefix("/api/v1")
        ->start()
        ->unwrap($error);
    
    if ($error) {
        die($error)
    }
}
```

You can now define your routes within the `src/api` directory.

There are some rules you will need to follow.

1. Each file must take the name `{method}.php`, where `{method}` is the http method your file accepts.
   | File Name                           | Final Web Path                      |
   |-------------------------------------|-------------------------------------|
   | src/api/get.php                     | __GET__ /api/v1                     |
   | src/api/about/get.php               | __GET__ /api/v1/about               |
   | src/api/about/`{username}`/get.php  | __GET__ /api/v1/about/`{username}`  |
   | src/api/about/`{username}`/post.php | __POST__ /api/v1/about/`{username}` |
   | ...                                 | ...                                 |
2. Each file must return a function.
   ```php
   // src/api/get.php
   use function CatPaw\Core\success;
   return static function(){
       return success("hello world");
   };
   ```

> [!NOTE]
> `{username}` is a path parameter, read more [here](./Server%20Path%20Parameters.md).