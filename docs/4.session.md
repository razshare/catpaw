# Session

Each session is created based on a client's _session-id_ cookie.

If the client already has a valid _session-id_ cookie, the session creation is skipped and the existing session is
retrieved instead.

> [!NOTE]
> A _session-id_ cookie is considered to be valid if the
> server has a mapping for the given _session-id_ and
> the session itself is not expired.

## How to start a session

Request a `SessionInterface` parameter in your context.

```php
<?php
use CatPaw\Web\Interfaces\ServerInterface;
use CatPaw\Web\Interfaces\SessionInterface;
use CatPaw\Web\Interfaces\RouterInterface;
use CatPaw\Web\Interfaces\ServerInterface;
use function CatPaw\Core\anyError;
use function CatPaw\Core\success;
use CatPaw\Core\Unsafe;

function handler(SessionInterface $session){
    return success();
}

function main(ServerInterface $server, RouterInterface $router): Unsafe {
    return anyError(function() use($server) {
        $router->get('/', handler(...))->try();
        $server->start()->try();
    });
}
```

That's it, you started a session.\
All you need to do is request a `SessionInterface` parameter.\
Your client has obtained a _session-id_ cookie and your server has mapped it.

## Writing to the session

You will need to access your parameter by reference.

```php
<?php
use CatPaw\Web\Interfaces\SessionInterface;
use CatPaw\Web\Interfaces\ServerInterface;
use CatPaw\Web\Interfaces\RouterInterface;
use function CatPaw\Core\anyError;
use function CatPaw\Core\success;
use CatPaw\Core\Unsafe;

function handler(SessionInterface $session) {
    $created = &$session->ref('created');   // get by reference
    $created = time();                      // update the session value
    return success($created);               // serve the result
}

function main(ServerInterface $server, RouterInterface $router): Unsafe {
    return anyError(function() use($server) {
        $router->get('/', handler(...))->try();
        $server->start()->try();
    });
}
```

Note the `&` in front of `$session->ref('created')`, you're modifying the session by reference.

## Restart

Whenever a session expires, the server will create a new one automatically.
> [!NOTE]
> The contents of the old session are lost.

## Custom sessions

You can customize your session behavior by overwriting the `SessionInterface` provider.

```php
<?php
use CatPaw\Web\Interfaces\SessionInterface;
use CatPaw\Web\Interfaces\ServerInterface;
use function CatPaw\Core\anyError;
use CatPaw\Core\Unsafe;
use CatPaw\Web\Server;

class CustomSession implements SessionInterface {
    // Your custom implementation goes here
}

function main(ServerInterface $server): Unsafe {
    return anyError(function() use($server) {
        Container::provide(SessionInterface::class, fn(Request $request) => new CustomSession($request));
        $server = Server::get();
        $server->start()->try();
    });
}
```
