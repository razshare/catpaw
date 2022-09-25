# What is this?

Catpaw is an opinionated group of libraries centered around `catpaw/core`, which is a dependency injection library built for [amphp](https://amphp.org/) that makes heavy use of php attributes.

# Table of Contents

| Topic                      | Implemented  | Repository                                                            | Read                                       |
|----------------------------|--------------|-----------------------------------------------------------------------|--------------------------------------------|
| 🌐 Web Route Handlers       | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/1.WebRouteHandlers.md)     |
| 🌐 Web Route Controllers    | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/14.WebRouteControllers.md) |
| 🌐 File System Web Routes   | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/17.FileSystemWebRoutes.md) |
| 🌐 Web Path Parameters      | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/2.WebPathParameters.md)    |
| 🌐 Web Path Not Found       | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/3.WebPathNotFound.md)      |
| 🌐 Web Session              | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/4.WebSession.md)           |
| 🌐 Open API                 | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/18.OpenAPI.md)             |
| ⚡ Entry                    | ✅           | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | [Github](./docs/5.Entry.md)                |
| 🌐 Web Byte Range Requests  | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/7.WebByteRangeRequests.md) |
| ⚡ Custom Attributes        | ✅           | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | [Github](./docs/8.CustomAttributes.md)     |
| 🌐 Web Filters              | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/9.WebFilters.md)           |
| 🌐 Web Sockets              | ✅           | [catpaw-web](https://github.com/tncrazvan/catpaw-web)                 | [Github](./docs/10.WebSockets.md)          |
| 💡 RaspberryPI             | ✅           | [catpaw-raspberrypi](https://github.com/tncrazvan/catpaw-raspberrypi) | [Github](./docs/11.RaspberryPI.md)         |
| ⚡ Services                 | ✅           | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | [Github](./docs/13.Services.md)            |
| 🗄 MySQL Repositories       | ✅           | [catpaw-mysql](https://github.com/tncrazvan/catpaw-mysql)             | __TODO__                                   |
| ⚡ Stores                   | ✅           | [catpaw-store](https://github.com/tncrazvan/catpaw-store)             | [Github](./docs/12.Stores.md)              |
| 🌐 Server Linking           | ❓           | __TODO__                                                              | [Github](./docs/19.ServerLinking.md)       |
| ⚡ Portable Build           | ✅           | [catpaw-core](https://github.com/tncrazvan/catpaw-core)               | __TODO__                                   |

# Starters

| Type     | Implemented | Description                                 | Read                                    | Github Template                                           |
|----------|-------------|---------------------------------------------|-----------------------------------------|-----------------------------------------------------------|
| App      | ✅          | using only the core libraries               | [Github](./README.md#get-started)       | [Use](https://github.com/tncrazvan/catpaw-starter)        |
| Web      | ✅          | create a web server                         | [Github](./docs/16.Web.md)              | [Use](https://github.com/tncrazvan/catpaw-web-starter)    |
| Svelte   | ✅          | using `Web` + [Svelte](https://svelte.dev/) | [Github](./docs/15.SvelteSPAStarter.md) | [Use](https://github.com/tncrazvan/catpaw-svelte-starter) |
| Vue      | ❌          | using `Web` + Vue                           | __TODO__                                | __TODO__                                                  |
| Markdown | ❌          | using `Web` + Markdown                      | __TODO__                                | __TODO__                                                  |
| Binary   | ❌          | ? 😸                                        | __TODO__                                | __TODO__                                                  |


### Using composer

- *App* 
    ```bash
    composer create-project catpaw/starter
    ```
- *Web*
    ```bash
    composer create-project catpaw/web-starter
    ```
- *Svelte* 
    ```bash 
    composer create-project catpaw/svelte-starter
    ```
<br/>

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
function main(){
    echo "hello world\n";
}
```

<br/>

After you've created your new project, you can run it using

```bash
compsoer watch
```
to watch file changes (useful in development)
or

```bash
compsoer start
```
for production mode.

# Build & Run

It is possible, but not required, to build your application into a single `.phar` file using

```bash
composer build
```
The building process can be configured inside the `build.yml` file.

After building your application, you can simply run it using 
```
php dist/app.phar
```
The resulting `.phar`, by default (check `build.yml`), file includes the following directories:

- `./src`
- `./vendor`
- `./resources`
- `./dist`
- `./bin`

which means it's a portable binary, you just need to make 
sure php is installed on whatever machine you're trying to run it on.

# A note on versioning

Given the versioning string `major.minor.patch`, all libraries that are compatible with eachother will always have the same `major` and `minor` versions.<br/>
Regardless if a library has actually had any major or minor changes, its version will be bumped to match all the other libraries to indicate that given library is compatible with the latest features.<br/>

The `patch` number may vary from library to library, but the `major` and `minor` numbers should all match.

For example, if you're using `catpaw/core:^0.2` and you want to add `catpaw/web` to your project, you should always pick `catpaw/web:^0.2` to match your core version.

### Why? 
I'm a single developer and this gives me more freedom to add features without breaking previous versions and I like to organize my projects this way.
# Looking for some examples?

You can follow along with the examples provided by the `catpaw/examples` repository at https://github.com/tncrazvan/catpaw-examples/tree/master/src.


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
            "port": 9003
        },
        {
            "name": "Main (paw)",
            "type": "php",
            "request": "launch",
            "program": "${workspaceFolder}/bin/start",
            "cwd": "${workspaceFolder}",
            "args": [
                "-e./src/main.php",
                "-l./src/lib/"
            ],
            "runtimeArgs": [
                "-dxdebug.start_with_request=yes",
                "-dxdebug.mode=debug",
            ],
            "env": {
                "XDEBUG_MODE": "debug",
                "XDEBUG_CONFIG": "client_port=${port}"
            }
        }
    ]
}
```

The first configuration will passively listen for xdebug, while the second one will launch the currently opened script.
