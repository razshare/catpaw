# What is this?

Catpaw is an opinionated group of libraries centered around `catpaw/core`, which is a dependency injection library built for [amphp](https://amphp.org/).

# Table of Contents

| Topic                      | Implemented | Repository                                                            | Read                                       |
|----------------------------|-------------|-----------------------------------------------------------------------|--------------------------------------------|
| Examples                   |             |                                                                       | [Github](https://github.com/tncrazvan/catpaw-examples/tree/master/src)                |
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
| 🌐 OpenAPI                 | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)         | _TODO_                                     |
| 🗄 MySQL Repositories      | ✅           | [catpaw-mysql](https://github.com/tncrazvan/catpaw-mysql)             | _TODO_                                     |
| ⚡ Stores                 | ✅           | [catpaw-store](https://github.com/tncrazvan/catpaw-store)               | [Github](./docs/12.Stores.md)            |
| 🌐 Svelte SPA                 | ✅           | [catpaw-svelte-starter](https://github.com/tncrazvan/catpaw-svelte-starter)               | [Github](./docs/15.SPA.md)            |
| ⚡ Queue                 | ✅           | [catpaw-queue](https://github.com/tncrazvan/catpaw-queue)               | TODO            |

---

# Premise

This project is aimed at linux distributions, some features may or not may work on Windows or MacOS.<br/>
Feel free to contribute fixing issues for specific platforms.

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

After you've created your new project, you can run it using

```bash
compsoer run watch
```

or

```bash
compsoer run start
```

# Get started with Svelte

In order to get started you will need [php 8.1](https://www.php.net/downloads.php) or a more recent version.

You can create a new project using the svelte starter template.

```bash
composer create-project catpaw/svelte-starter
```

Or you could also clone the template from https://github.com/tncrazvan/catpaw-svelte-starter


During development you need to run both the Vite and the CatPaw servers.<br/>
The Vite server will act as a proxy for your `/api/*` and `*:state` requests, redirecting them to the CatPaw server.<br/>

First off start your CatPaw server using
```bash
composer start
```
or
```bash
composer watch
```

then install your npm dependencies

```bash
npm i
```

and start your vite dev server

```bash
npm run dev
```

all your communication with CatPaw will happen through the Vite proxy, so you'll be working on [http://127.0.0.1:3000](http://127.0.0.1:3000/) and your apis will should be exposed under `/api/*`.

### Build for production

You can build your project for production using
```
npm run build
```
then you can start your server in production by running
```bash
./start
```

### Note

Don't use the Vite server in production, build your project and run the CatPaw server.



### Looking for some examples?

You can follow along with the examples provided by the `catpaw/examples` repository at https://github.com/tncrazvan/catpaw-examples/tree/main/src.


# Debugging with VSCode

In order to debug with vscode you will need to configure both vscode and xdebug (3.x).

### XDebug 3.x configuration for VSCode

In your `php.ini` file add:
```ini
[xdebug]
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```

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
        "XDEBUG_MODE": "debug",
        "XDEBUG_CONFIG": "client_port=${port}"
      }
    }
  ]
}
```

The first configuration will passively listen for xdebug, while the second one will launch the currently opened script.

### Note 

When launching the "currently opened file" profile, make sure that file includes in some manner a global `main` function.<br/>
Regardless, the cli should warn you if such function is not present in the global scope.
