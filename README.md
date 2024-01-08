# What is this?

Catpaw is an opinionated group of libraries centered around `catpaw/core`, which is a dependency injection library built for [amphp](https://amphp.org/) that makes heavy use of php attributes.

# Table of Contents

| Topic                  | Implemented | Read |
|------------------------|-------------|------|
| 🌐 Router              | ✅           |      |
| 🌐 Filesystem Router   | ✅           |      |
| 🌐 Route Filters       | ✅           |      |
| 🌐 Path Parameters     | ✅           |      |
| 🌐 Route Not Found     | ✅           |      |
| 🌐 Open API            | ✅           |      |
| 🌐 Route Session       | ✅           |      |
| 🌐 Byte Range Requests | ✅           |      |
| ⚡ Entry                | ✅           |      |
| ⚡ Custom Attributes    | ✅           |      |
| 💡 RaspberryPI         | ✅           |      |
| ⚡ Services             | ✅           |      |
| ⚡ Stores               | ✅           |      |
| ⚡ Queues               | ✅           |      |
| 💡 Text                | ✅           |      |
| 💡 Schedule            | ✅           |      |


> [!NOTE]
> This project is aimed at linux distributions, some features may or not may work on Windows or MacOS.\
> Feel free to contribute fixing issues for specific platforms.

# Get started

In order to get started you will need [php 8.2](https://www.php.net/downloads.php) or a more recent version.

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
composer dev:watch
```
to watch file changes (useful in development)
or

```bash
composer prod:start
```
for production mode.

# Build & Run

It is possible, but not required, to build your application into a single `.phar` file using

```bash
composer prod:build
```
The building process can be configured inside the `build.yml` file.

After building your application, you can simply run it using 
```
php app.phar
```
The resulting `.phar`, by default (check `build.yml`), includes the following directories:

- `./src`
- `./vendor`
- `./server`
- `./bin`
- `./.build-cache` (created at comptile time)

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
            "name": "Listen",
            "type": "php",
            "request": "launch",
            "port": 9003
        },
        {
            "name": "Start",
            "type": "php",
            "request": "launch",
            "program": "${workspaceFolder}/bin/start",
            "cwd": "${workspaceFolder}",
            "args": [
                "--libraries='./src/lib/'",
                "--entry='./src/main.php'"
            ],
            "runtimeArgs": [
                "-dxdebug.start_with_request=yes",
                "-dxdebug.mode=debug"
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
