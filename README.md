# Table of Contents

| Topic                      | Last update | Implemented | Repository                                                            | Read                                       |
|----------------------------|-------------|-------------|-----------------------------------------------------------------------|--------------------------------------------|
| Intro                      | 1.0         |             | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | [Github](./docs/0.Intro.md)                |
| 🌐 Web Route Handlers      | 1.0         | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/1.WebRouteHandlers.md)     |
| 🌐 Web Path Parameters     | 1.0         | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/2.WebPathParameters.md)    |
| 🌐 Web Path Not Found      | 1.0         | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/3.WebPathNotFound.md)      |
| 🌐 Web Session             | 1.0         | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/4.WebSession.md)           |
| ⚡ Entry                    | 1.0         | ✅           | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | [Github](./docs/5.Entry.md)                |
| ⚡ Modules                  | 1.0         | ✅           | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | [Github](./docs/6.Modules.md)              |
| 🌐 Web Byte Range Requests | 1.0         | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/7.WebByteRangeRequests.md) |
| ⚡ Custom Attributes        | 1.0         | ✅           | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | [Github](./docs/8.CustomAttributes.md)     |
| 🌐 Web Filters             | 1.0         | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/9.WebFilters.md)           |
| 🌐 Web Sockets             | 1.0         | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/10.WebSockets.md)          |
| 💡 RaspberryPI             | 1.0         | ✅           | [catpaw-raspberrypi](https://github.com/tncrazvan/catpaw-raspberrypi) | [Github](./docs/11.RaspberryPI.md)         |
| ⚡ Services & Singletons    | 1.0         | ✅           | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | _TODO_                                     |
| 🌐 OpenAPI                 | 1.0         | ✅           | [catpaw-openapi](https://github.com/tncrazvan/catpaw-openapi)         | _TODO_                                     |
| 🗄 MySQL Repositories      | 1.0         | ✅           | [catpaw-mysql](https://github.com/tncrazvan/catpaw-mysql)             | _TODO_                                     |
| 🗄 Redis Repositories      | ~           | ❌           |                                                                       | _TODO_                                     |

---

# Get started

In order to get started you will need [php 8.0](https://www.php.net/downloads.php) or a more recent version.

All you need to do is create a new project using the starter template.

```bash
composer create-project catpaw/starter
```

Or you could also clone the template from https://github.com/tncrazvan/catpaw-starter

---

Every application must declare a ```main``` function in the global scope, that will be your entry point:

```php
<?php
// src/main.php
namespace {
    function main(){
        echo "hello world\n";
    }
}
```

<br/>

After you've created your new project, you can run it in development mode using

```bash
compsoer run dev
```

or in production using

```bash
compsoer run start
```

### Interested in web server examples?

You can follow along with the examples provided by the `catpaw/web` package itself
at https://github.com/tncrazvan/catpaw-web/tree/master/examples.

## Note

This project is aimed at linux distributions and has not been tested properly on Windows or Mac machines.
