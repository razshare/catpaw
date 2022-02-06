# Table of Contents

| Topic               | Last update | Link                                   |
|---------------------|-------------|----------------------------------------|
| Intro               | 1.0.0       | [Github](./docs/0.Intro.md)            |
| Route Handlers      | 1.0.0       | [Github](./docs/1.RouteHandlers.md)    |
| Parameters          | 1.0.0       | [Github](./docs/2.Parameters.md)       |
| Not Found           | 1.0.0       | [Github](./docs/3.NotFound.md)         |
| Session             | 1.0.0       | [Github](./docs/4.Session.md)          |
| Entry               | 1.0.0       | [Github](./docs/5.Entry.md)            |
| Modules             | 1.0.0       | [Github](./docs/6.Modules.md)          |
| Byte Range Requests | 1.0.0       | [Github](./docs/7.ByteRange.md)        |
| Custom Attributes   | 1.0.0       | [Github](./docs/8.CustomAttributes.md) |
| Filters             | 1.0.0       | [Github](./docs/9.Filters.md)          |
| WebSockets          | 1.0.0       | [Github](./docs/10.WebSockets.md)      |

---

## Get started

In order to get started you will need [php 8.0](https://www.php.net/downloads.php) or a more recent version.

All you need to do is create a new project using the starter template.

```bash
composer create-project catpaw/starter
```

Or you could also clone the template from https://github.com/tncrazvan/catpaw-starter

---

After you've created your new project, you can run it in development mode using

```bash
compsoer run dev
```

or in production using

```bash
compsoer run start
```

after that's done, your server will be up and running on port ```8080```.


---

## Basics

Every application must declare a `main` function in the global scope, that will be your entry point.
<br/>

This `main` function can request dependency injections.

```php
<?php

namespace {
    use CatPaw\CatPaw;
    use CatPaw\Tools\Helpers\Route;
    use CatPaw\Attributes\StartWebServer;
    
    #[StartWebServer]
    function main() {
        Route::get("/cats", function() {
            return "there are no cats here";
        });
    }
}
```
