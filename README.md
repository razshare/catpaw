# What is this?

Catpaw is an opinionated dependency injection library that comes with batteries included for developing asynchronous and declarative general purpose programs.\
It leverages [php attributes](https://www.php.net/manual/en/language.attributes.overview.php) to provide declarative apis, and the [amphp](https://github.com/amphp/amp) platform to make your program asynchronous.


| Table of Contents                                                 | Description |
|-------------------------------------------------------------------|-------------|
| 📦 [Container](./docs/Container.md)                               | Provide dependencies and retrieve them. |
| 📦 [Constructors](./docs/Constructors.md)                                       | Execute code when a dependency is resolved. |
| 📦 [Entry](./docs/Entry.md)                                       | Execute code when a dependency is resolved. |
| ⚠️ [Error Management](./docs/Error%20Management.md)               | Manage errors. |
| 🌠 [Server](./docs/Server.md)                                     | Start a server. |
| 🚆 [Server Router](./docs/Server%20Router.md)                     | Define routes. |
| 📃 [Server Path Parameters](./docs/Server%20Path%20Parameters.md) | Define path parameters for your routes. |
| 🎫 [Server Session](./docs/Server%20Session.md)                   | Manage sessions. |
| 📞 [Server Websockets](./docs/Server%20Websockets.md)             | Serve websockets. |
| 💠 [Server Open Api](./docs/Server%20Open%20Api.md)               | Generate an Open Api definition. |
| 🎛️ [Command](./docs/Command.md)                                   | Create a console command. |
| 🗄️ [Database](./docs/Database.md)                                 | Connect to a database and send queries. |
| 🗄️ [Stores](./docs/Stores.md)                                     | Store data in memory and react to changes in said data. |
| 🚥 [Queues](./docs/Queues.md)                                     | Create in memory queues and tag them. |
| 🚥 [Signals](./docs/Signals.md)                                   | Create signals and react to them. |
| 🕐 [Schedule](./docs/Schedule.md)                                 | Schedule code execution using a human readable format. |
| 🏗️ [Build](./docs/Build.md)                                       | Build your project into one single portable file. |
| 💡 [RaspberryPi](./docs/RaspberryPi.md)                           | Control your RaspberryPi's GPIOs. |


> [!NOTE]
> This project is aimed at linux distributions, some features may or not may work on Windows and/or MacOS.\
> Feel free to contribute fixing issues for specific platforms.

# Get started

You will need at least [php 8.3](https://www.php.net/downloads.php) and the `php8.3-mbstring` and `php8.3-dom` extensions (required for PHPUnit).

> [!NOTE]
> I recommend you also install `php8.3-curl` for faster project initialization through composer.

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
- `./.build-cache` (created at build time)

which means it's a portable binary, you just need to make
sure php is installed on whatever machine you're trying to run it on.

# Debugging with VSCode

- Install xdebug
  ```php
  apt install php8.3-xdebug
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
