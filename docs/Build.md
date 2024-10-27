# Build

You can build your whole project into a single `.phar` file with
```sh
php -dphar.readonly=0 catpaw.phar --build
```

> [!NOTE]
> You can download the latest _catpaw.phar_ program from the [releases page](https://github.com/tncrazvan/catpaw/releases).

> [!NOTE]
> The `-dphar.readonly=0` option is required because the program needs permissions to write the _.phar_ file.

# Configuration
The configuration file is a _build.ini_ file

```ini
name = app
entry = src/main.php
libraries = src/lib
environment = env.ini
match = "/(^\.\/(\.build-cache|src|vendor|resources|bin)\/.*)|(\.\/env\.ini)/"
```

- `name` is the output name of your bundle.\
  The program will append _.phar_ to this name if not already specified in the configuration.
- `entry` the entry file of your application.\
  This is the file that contains your `main` function.
- `libraries` a list of directories, separated by `,`, that contain php services.\
  These services will be passed up to the dependency injection container.
- `environment` the environment file of your application, usually `env.ini`.\
  This is the same file that you usually pass in when you run `composer dev:start -- --environment=env.ini`.\
  This environment file is not required and can be overwritten at runtime by simply passing in the option
  ```sh
  php app.phar --environment=my-env.ini
  ```
- `match` a regular expression matching all other files you want bundled in your _.phar_ file.


# Optimize

You can shake off all your dev dependencies and make the bundle smaller by passing `--build-optimize`.

```sh
php -dphar.readonly=0 ./catpaw.phar --build --build-optimize
```

> [!NOTE]
> More optimization features may come in the future.
