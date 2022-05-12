# What is this?

Catpaw is an opinionated group of libraries centered around `catpaw/core`, which is a dependency injection library built for [amphp](https://amphp.org/).

# Table of Contents

| Topic                      | Implemented | Repository                                                            | Read                                       |
|----------------------------|-------------|-----------------------------------------------------------------------|--------------------------------------------|
| Examples                   |             |                                                                       | [Github](https://github.com/tncrazvan/catpaw-examples/tree/main/src)                |
| Intro                      |             |                                                                       | [Github](./docs/0.Intro.md)                |
| 🌐 Web Route Handlers      | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/1.WebRouteHandlers.md)     |
| 🌐 Web Path Parameters     | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/2.WebPathParameters.md)    |
| 🌐 Web Path Not Found      | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/3.WebPathNotFound.md)      |
| 🌐 Web Session             | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/4.WebSession.md)           |
| ⚡ Entry                    | ✅           | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | _TO-REDO_                |
| ⚡ Modules                  | ✅           | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | [Github](./docs/6.Modules.md)              |
| 🌐 Web Byte Range Requests | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/7.WebByteRangeRequests.md) |
| ⚡ Custom Attributes        | ✅           | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | [Github](./docs/8.CustomAttributes.md)     |
| 🌐 Web Filters             | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/9.WebFilters.md)           |
| 🌐 Web Sockets             | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/10.WebSockets.md)          |
| 💡 RaspberryPI             | ✅           | [catpaw-raspberrypi](https://github.com/tncrazvan/catpaw-raspberrypi) | [Github](./docs/11.RaspberryPI.md)         |
| ⚡ Services & Singletons    | ✅           | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | _TODO_                                     |
| 🌐 OpenAPI                 | ✅           | [catpaw-openapi](https://github.com/tncrazvan/catpaw-openapi)         | _TODO_                                     |
| 🗄 MySQL Repositories      | ✅           | [catpaw-mysql](https://github.com/tncrazvan/catpaw-mysql)             | _TODO_                                     |
| 🗄 Redis Repositories      | ❌           |                                                                       | _TODO_                                     |
| ⚡ Stores                 | ✅           | [catpaw-store](https://github.com/tncrazvan/catpaw-store)               | [Github](./docs/12.Stores.md)            |

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

### Looking for some examples?

You can follow along with the examples provided by the `catpaw/examples` repository at https://github.com/tncrazvan/catpaw-examples/tree/main/src.

## Note

This project is aimed at linux distributions and has not been tested properly on Windows or Mac machines.
