# What is this?

Catpaw is an opinionated dependency injection library that comes with batteries included for developing asynchronous and declarative general purpose programs.\
It leverages the [amphp](https://github.com/amphp/amp) platform to make your program asynchronous.


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
| 💡 [RaspberryPi](./docs/RaspberryPi.md)                           | Control your RaspberryPi's GPIOs. |


> [!NOTE]
> This project is aimed at linux distributions, some features may or not may work on Windows and/or MacOS.\
> Feel free to contribute fixing issues for specific platforms.

# Prerequisites

You will need at least [php 8.3](https://www.php.net/downloads.php), `ext-mbstring`, `ext-dom` and `inotify-tools` for [watch mode](#watch-mode).\
Although optional, I also recommend you install `ext-curl` to speed http requests in php.

```sh
sudo apt install php-mbstring  # this contains ext-mbstring
sudo apt install php-xml       # this contains ext-dom
sudo apt install php-curl      # this will speed up project creation and http requests in general
sudo apt install inotify-tools # this allows you to use watch mode (read below)
```

# Get started

Create a new project using one of the starter templates.

You can start from scratch
```bash
composer create-project catpaw/starter
```

or you can start with a web server
```bash
composer create-project catpaw/web-starter
```

# Install Dependencies
```bash
make install
```

# Program Structure

Every program must declare a `main` function in the global scope, that will be your entry point.

```php
// src/main.php
use Psr\Log\LoggerInterface;
function main(LoggerInterface $logger){
  $logger->info("hello world");
}
```

You can run your program in one of three modes.

# Development Mode

Enter Development Mode with

```bash
make dev
```

This mode will run your program with [XDebug](https://xdebug.org) enabled.

> [!NOTE]
> See [section Debugging with VSCode](#debugging-with-vscode)


# Watch Mode

Enter Watch Mode with

```bash
make watch
```

This mode will run your program with [XDebug](https://xdebug.org) enabled and 
it will restart your program every time you make a change to your source code.

> [!NOTE]
> See [section Debugging with VSCode](#debugging-with-vscode)

> [!NOTE]
> By default "source code" means the "src" directory.\
> You can change this configuration in your [makefile](./makefile), see section `watch`, parameter `resources`.

# Production Mode

Enter Production Mode with

```bash
make start
```

It's just as it sounds, run your program directly.\
No debuggers, not extra overhead.

# Build

It is possible, but no required, to bundle your program into a single `.phar` file with

```bash
make build
```

The building process can be configured inside the `build.ini` file.

After building your application, you can simply run it using
```
php out/app.phar
```
The resulting `.phar` will include the following directories

- `src`
- `vendor`
- `.build-cache` (created at build time)

It's a portable bundle, you just need to make
sure php is installed on whatever machine you're trying to run it on.

# Debugging with VSCode

Install xdebug
```php
sudo apt install php8.3-xdebug
```

Configure your `.vscode/launch.json`
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen",
            "type": "php",
            "request": "launch",
            "port": 9003
        }
    ]
}
```

Start debugging.
