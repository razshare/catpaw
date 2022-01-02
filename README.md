# Table of Contents

| Topic             | Last update   | Link                                  |
|-------------------|---------------|---------------------------------------|
| Intro             | 2.4.173       | [Github](docs/0.Intro.md)             |
| Basics            | 2.4.173       | [Github](docs/1.RouteHandlers.md)            |
| Parameters        | 2.4.173       | [Github](docs/2.Parameters.md)        |
| NotFound          | 2.4.173       | [Github](docs/3.NotFound.md)          |
| Session           | 2.4.173       | [Github](docs/4.Session.md)           |
| Entry             | 2.4.173       | [Github](docs/5.Entry.md)             |
| Modules           | 2.4.173       | [Github](docs/6.Modules.md)           |

---

## Get started

In order to get started you will need [php 8.0](https://www.php.net/downloads.php) or a more recent version.

All you need to do is create a new project using the starter template.

```bash
composer create-project razshare/catpaw-starter
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

Within your `main` function, you can benefic from dependency injections, for example you could start a web server using
the `MainConfiguration`.

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