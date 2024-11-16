# Session

Sessions start automatically when injected.

```php
<?php
// src/api/get.php
use CatPaw\Web\Interfaces\ServerInterface;

return function(SessionInterface $session){
    // Session has now started.
    return $session->id();
}
```

# Modify Session

Session contents are mutable and can be modified by reference.

```php
<?php
// src/api/get.php
use CatPaw\Web\Interfaces\ServerInterface;

return function(SessionInterface $session){
    $username = &$session->ref('username');
    $username = "John";
}
```

# Destroy Session

```php
// src/api/get.php
use CatPaw\Web\Interfaces\ServerInterface;

return function(SessionInterface $session){
    $session->destroy();
    // Any changes to the session below this point will be lost.
}
```

# Restart Session

Sessions restart automatically when they expire.\
There is no direct mechanism for restarting a session.

In order to restart the session of a client, you must [destroy](#destroy-session) that client's session.