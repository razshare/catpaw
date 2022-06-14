# What is this?

Catpaw is an opinionated group of libraries centered around `catpaw/core`, which is a dependency injection library built for [amphp](https://amphp.org/).

# Table of Contents

| Topic                      | Implemented | Repository                                                            | Read                                       |
|----------------------------|-------------|-----------------------------------------------------------------------|--------------------------------------------|
| Examples                   |             |                                                                       | [Github](https://github.com/tncrazvan/catpaw-examples/tree/main/src)                |
| Get started & debug        |             |                                                                       | [Github](./docs/0.Intro.md)                |
| 🌐 Web Route Handlers      | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/1.WebRouteHandlers.md)     |
| 🌐 Web Route Controllers   | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/14.WebRouteControllers.md) |
| 🌐 Web Path Parameters     | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/2.WebPathParameters.md)    |
| 🌐 Web Path Not Found      | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/3.WebPathNotFound.md)      |
| 🌐 Web Session             | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/4.WebSession.md)           |
| ⚡ Entry                    | ✅           | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | [Github](./docs/5.Entry.md)                |
| 🌐 Web Byte Range Requests | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/7.WebByteRangeRequests.md) |
| ⚡ Custom Attributes        | ✅           | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | [Github](./docs/8.CustomAttributes.md)     |
| 🌐 Web Filters             | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/9.WebFilters.md)           |
| 🌐 Web Sockets             | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/10.WebSockets.md)          |
| 💡 RaspberryPI             | ✅           | [catpaw-raspberrypi](https://github.com/tncrazvan/catpaw-raspberrypi) | [Github](./docs/11.RaspberryPI.md)         |
| ⚡ Services                 | ✅           | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | [Github](./docs/13.Services.md)            |
| 🌐 OpenAPI                 | ✅           | [catpaw-openapi](https://github.com/tncrazvan/catpaw-openapi)         | _TODO_                                     |
| 🗄 MySQL Repositories      | ✅           | [catpaw-mysql](https://github.com/tncrazvan/catpaw-mysql)             | _TODO_                                     |
| ⚡ Stores                 | ✅           | [catpaw-store](https://github.com/tncrazvan/catpaw-store)               | [Github](./docs/12.Stores.md)            |

---

# Get started

In order to get started you will need [php 8.1](https://www.php.net/downloads.php) or a more recent version.

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


# Debugging with VSCode

In order to debug with vscode you will need to configure both vscode and xdebug (3.x).

### VSCode configuration

Make new a `./.vscode/launch.json` file in your project and add the following configuration if you don't have it already:
```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen (paw)",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "runtimeArgs": ["-dxdebug.start_with_request=yes", "-dxdebug.mode=debug"]
    },
    {
      "name": "Launch (paw)",
      "type": "php",
      "request": "launch",
      "program": "${workspaceFolder}/vendor/catpaw/core/scripts/start.php",
      "cwd": "${workspaceFolder}",
      "args": ["${file}"],
      "port": 0,
      "runtimeArgs": ["-dxdebug.start_with_request=yes", "-dxdebug.mode=debug"],
      "env": {
        "XDEBUG_MODE": "debug,develop",
        "XDEBUG_CONFIG": "client_port=${port}"
      }
    }
  ]
}
```

The first configuration will passively listen for xdebug, while the second one will launch the currently opened script.

### XDebug 3.x configuration for VSCode

In your `php.ini` file add:
```ini
[xdebug]
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```

# Debugging with PHPStorm

If you're using PHPStorm you will need to start listening for PHP Xdebug connections.

First off pick your php interpreter:

![image](https://user-images.githubusercontent.com/6891346/168439592-3c8609aa-2d30-4995-ace3-8fa19fcef4b0.png)

Then start listening for xdebug connections: ![image](https://user-images.githubusercontent.com/6891346/168439662-558102d8-a94d-4480-a4e5-324f85a47cab.png)

### Xdebug 3.x configuration for PHPStorm

```ini
xdebug.mode=debug
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.start_with_request=yes
```

# Start

You should now be able to run your project with 
```bash
composer start
```
or
```bash
./start
```
for a faster startup.

You can debug in both modes with both vscode and phpstorm listening for xdebug connections.
