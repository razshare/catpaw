# What is this?

Catpaw is an opinionated dependency injection library that comes with batteries included for developing asynchronous and declarative general purpose programs.\
It leverages [php attributes](https://www.php.net/manual/en/language.attributes.overview.php) to provide declarative apis, and the [amphp](https://github.com/amphp/amp) platform to make your program asynchronous.


| Table of Contents                                         |
|-----------------------------------------------------------|
| ⚡ [Error Management](./docs/0.error-managament.md)        |
| 🌐 [Router](./docs/1.router.md)                           |
| 🌐 [Path Parameters](./docs/2.path-parameters.md)         |
| 🌐 [Open Api](./docs/18.open-api.md)                      |
| 🌐 [Session](./docs/4.session.md)                         |
| 🌐 [Byte Range Requests](./docs/7.byte-range-requests.md) |
| ⚡ [Entry](./docs/5.entry.md)                              |
| ⚡ [Custom Attributes](./docs/8.custom-attributes.md)      |
| 💡 [RaspberryPi](./docs/11.raspberrypi.md)                |
| ⚡ [Services](./docs/13.services.md)                       |
| ⚡ [Stores](./docs/12.stores.md)                           |
| ⚡ [Queues](./docs/21.queues.md)                           |
| ⚡ [Schedule](./docs/22.schedule.md)                       |
| ⚡ [Build](./docs/23.build.md)                             |
| 🌐 [Websockets](./docs/24.websockets.md)                  |
| 🌐 [View (Twig)](./docs/25.view.md)                              |
| ⚡ [State](./docs/26.state.md)                             |
| ⚡ [Signals](./docs/27.signals.md)                             |
| ⚡ [Go interop](./docs/28.goffi.md)                             |


> [!NOTE]
> This project is aimed at linux distributions, some features may or not may work on Windows and/or MacOS.\
> Feel free to contribute fixing issues for specific platforms.

# Get started

You will need at least [php 8.2](https://www.php.net/downloads.php) and the `php-mbstring` extension.

Create a new project using one of the starter templates.

- you can start from scratch
  ```bash
  composer create-project catpaw/starter
  ```
- you can start with a web server
  ```bash
  composer create-project catpaw/web-starter
  ```
---

Every application must declare a `main` function in the global scope, that will be your entry point:

```php
<?php
// src/main.php
use Psr\Log\LoggerInterface;
function main(LoggerInterface $logger){
  $logger->info("hello world");
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
The building process can be configured inside the `build.ini` file.

After building your application, you can simply run it using
```
php app.phar
```
The resulting `.phar`, by default (check `build.ini`), includes the following directories:

- `./src`
- `./vendor`
- `./bin`
- `./.build-cache` (created at comptile time)

which means it's a portable binary, you just need to make
sure php is installed on whatever machine you're trying to run it on.

# Debugging with VSCode

- Install xdebug
  ```php
  apt install php8.2-xdebug
  ```

- Put this configuration in your `./.vscode/launch.json` file
  ```json
  {
      "version": "0.2.0",
      "configurations": [
          {
              "name": "Launch",
              "type": "php",
              "request": "launch",
              "program": "${workspaceRoot}/bin/start",
              "args": [
                  "--libraries='./src/lib'",
                  "--entry='./src/main.php'"
              ],
              "cwd": "${workspaceRoot}",
              "runtimeArgs": [
                  "-dxdebug.start_with_request=yes"
              ],
              "env": {
                  "XDEBUG_MODE": "debug,develop",
                  "XDEBUG_CONFIG": "client_port=${port}"
              }
          },
          {
              "name": "Listen",
              "type": "php",
              "request": "launch",
              "port": 9003
          }
      ]
  }
  ```
- Start debugging
