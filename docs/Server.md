# Server

You can start a web server using `$server->start()`.

```php
use CatPaw\Web\Interfaces\ServerInterface;
function main(ServerInterface $server) {
    return $server->start();
}
```